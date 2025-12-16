const API_BASE = '../api.php'
const STORAGE_KEY = 'bb_cart'
const Utils = {
  formatCurrency: (amount) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(Number(amount || 0)),
  apiCall: async (query, options = {}) => {
    const m = String(options.method || 'GET').toUpperCase()
    const baseHeaders = { 'Content-Type': 'application/json' }
    if (m !== 'GET') {
      const csrf = Utils.getCsrfToken() || ''
      baseHeaders['X-CSRF-Token'] = csrf
      baseHeaders['X-Requested-With'] = 'XMLHttpRequest'
      if (options.body) {
        try {
          const isString = typeof options.body === 'string'
          const payload = isString ? JSON.parse(options.body || '{}') : (options.body || {})
          if (payload && typeof payload === 'object' && !payload.csrf_token) { payload.csrf_token = csrf }
          options.body = JSON.stringify(payload)
        } catch (e) {
          options.body = JSON.stringify({ csrf_token: csrf })
        }
      } else {
        options.body = JSON.stringify({ csrf_token: csrf })
      }
    }
    try {
      const res = await fetch(`${API_BASE}${query}`, { credentials: 'include', headers: { ...baseHeaders, ...(options.headers || {}) }, ...options })
      const ct = res.headers.get('content-type') || ''
      if (!ct.includes('application/json')) { return { success: false, error: 'Invalid response type' } }
      const data = await res.json()
      return data
    } catch (e) {
      return { success: false, error: e?.message || 'Network error' }
    }
  },
  getCsrfToken: () => {
    const match = document.cookie.match(/(?:^|; )csrf_token=([^;]+)/)
    return match ? decodeURIComponent(match[1]) : null
  },
  buildImage: (path) => {
    if (!path) return null
    const t = String(path).trim()
    if (/^https?:\/\//i.test(t)) return t
    const n = t.replace(/^\/+/, '')
    return `${window.location.origin}/${n}`
  }
}
const ShopState = {
  cart: JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]'),
  save() { localStorage.setItem(STORAGE_KEY, JSON.stringify(this.cart)) },
  add(product) {
    const idx = this.cart.findIndex(i => i.id === product.id)
    if (idx >= 0) { this.cart[idx].qty += 1 } else { this.cart.push({ id: product.id, name: product.name, price: Number(product.price || 0), image: product.image || '', qty: 1 }) }
    this.save(); MiniCart.update()
  },
  remove(id) { this.cart = this.cart.filter(i => i.id !== id); this.save(); MiniCart.update() },
  updateQty(id, qty) { const item = this.cart.find(i => i.id === id); if (!item) return; item.qty = Math.max(1, qty); this.save(); MiniCart.update() },
  subtotal() { return this.cart.reduce((s, i) => s + (Number(i.price || 0) * Number(i.qty || 0)), 0) }
}
const Catalog = {
  products: [],
  categories: [],
  view: 'grid',
  q: '',
  sort: 'relevance',
  activeFilters: new Set(),
  async init() { await this.loadCategories(); await this.loadProducts(); this.bindEvents(); MiniCart.update() },
  async loadCategories() { try { const r = await Utils.apiCall('?controller=category&action=list'); let raw = r.data || []; const pick = new Map(); raw.forEach(c => { const key = String(c?.name || '').trim().toLowerCase().replace(/\s+/g, ' '); const curr = pick.get(key); const pc = Number(c?.product_count || 0); if (!curr || pc > Number(curr.product_count || 0) || (pc === Number(curr.product_count || 0) && Number(c.id) < Number(curr.id))) { pick.set(key, c) } }); this.categories = Array.from(pick.values()); this.renderCategories() } catch (e) { } },
  async loadProducts(categoryId = null) {
    try {
      const grid = document.getElementById('productsGrid'); const empty = document.getElementById('emptyState'); if (grid) { const ph = Array.from({ length: 8 }).map(() => `<div class="skeleton-card"><div class="skeleton-image"></div><div class="skeleton-content"><div class="skeleton-line" style="width:65%"></div><div class="skeleton-line" style="width:40%"></div></div></div>`).join(''); grid.innerHTML = ph; if (empty) empty.style.display = 'none' }
      let endpoint = '?controller=product&action=listPublic&limit=50&is_active=1'
      if (categoryId) endpoint += `&category_id=${categoryId}`
      if (this.q) endpoint += `&search=${encodeURIComponent(this.q)}`
      const r = await Utils.apiCall(endpoint)
      let p = r.data?.products || r.data || []
      if (this.sort === 'price_asc') p.sort((a, b) => Number(a.price || 0) - Number(b.price || 0))
      else if (this.sort === 'price_desc') p.sort((a, b) => Number(b.price || 0) - Number(a.price || 0))
      else if (this.sort === 'newest') p.sort((a, b) => new Date(b.created_at || 0) - new Date(a.created_at || 0))
      this.products = p
      this.renderProducts()
    } catch (e) { this.products = []; this.renderProducts() }
  },
  bindEvents() {
    const s = document.getElementById('searchInput');
    if (s) {
      let t;
      s.addEventListener('input', () => {
        clearTimeout(t);
        t = setTimeout(() => {
          this.q = s.value;
          this.loadProducts()
        }, 300)
      })
    }

    const sortSel = document.getElementById('sortSelect');
    if (sortSel) {
      sortSel.addEventListener('change', () => {
        this.sort = sortSel.value;
        this.loadProducts()
      })
    }

    // Removed Grid/List toggle - default to grid view only

    const reset = document.getElementById('resetFilters');
    if (reset) {
      reset.addEventListener('click', () => {
        this.q = '';
        if (s) s.value = '';
        this.activeFilters.clear();
        this.loadProducts()
      })
    }
  },
  renderCategories() {
    const nav = document.getElementById('categoriesNav');
    if (!nav) return;

    // Render as pills/tabs instead of dropdown
    const pills = ['<a href="#" class="category-pill active" data-category="all"><i class="fas fa-th mr-1"></i>Semua</a>']
      .concat(this.categories.slice(0, 5).map(c => `<a href="#" class="category-pill" data-category="${c.id}">${c.name}</a>`))
      .join('');

    nav.innerHTML = pills;

    // Add click handlers
    nav.querySelectorAll('.category-pill').forEach(pill => {
      pill.addEventListener('click', (e) => {
        e.preventDefault();
        const categoryId = pill.getAttribute('data-category');

        // Update active state
        nav.querySelectorAll('.category-pill').forEach(p => p.classList.remove('active'));
        pill.classList.add('active');

        // Load products
        this.loadProducts(categoryId === 'all' ? null : categoryId);
      });
    });
  },
  renderProducts() {
    const grid = document.getElementById('productsGrid'); const empty = document.getElementById('emptyState'); if (!grid) return; if (!this.products.length) { grid.innerHTML = ''; if (empty) empty.style.display = 'grid'; return } if (empty) empty.style.display = 'none';
    const cls = this.view === 'list' ? 'products-list' : ''
    grid.className = `products-grid ${cls}`
    grid.innerHTML = this.products.map(p => {
      const img = Utils.buildImage(p.image_url || p.image)
      const inCart = ShopState.cart.find(x => String(x.id) === String(p.id))
      const qty = Number(inCart?.qty || 0)

      // Stock indicator
      const stock = Number(p.stock_quantity || 0)
      let stockBadge = ''
      if (stock > 10) {
        stockBadge = '<div class="stock-badge stock-available"><i class="fas fa-check-circle"></i> Tersedia</div>'
      } else if (stock > 0) {
        stockBadge = '<div class="stock-badge stock-low"><i class="fas fa-exclamation-circle"></i> Stok Terbatas</div>'
      } else {
        stockBadge = '<div class="stock-badge stock-out"><i class="fas fa-times-circle"></i> Habis</div>'
      }

      return `
        <div class="product-card">
          ${stockBadge}
          <img class="product-image" src="${img || ''}" alt="${p.name || ''}" onerror="this.style.display='none'">
          <div class="product-info">
            <div class="product-title">${p.name || ''}</div>
            <div class="product-price">${Utils.formatCurrency(p.price || 0)}</div>
            <div class="product-actions-row">
              <div class="product-quantity" style="display: flex;">
                <button class="quantity-btn" data-act="dec" data-id="${p.id}" ${qty <= 0 ? 'disabled' : ''}><i class="fas fa-minus"></i></button>
                <span class="quantity-value">${qty}</span>
                <button class="quantity-btn" data-act="inc" data-id="${p.id}" ${stock <= 0 ? 'disabled' : ''}><i class="fas fa-plus"></i></button>
              </div>
              <button class="btn btn-primary btn-buy" data-id="${p.id}" data-role="buy" ${stock <= 0 ? 'disabled' : ''}>Beli</button>
            </div>
          </div>
        </div>`
    }).join('')

    // Update quantity UI function
    const updateQtyUI = (id) => {
      const qtySpan = grid.querySelector(`.quantity-value[data-id="${id}"], .product-quantity span.quantity-value`)
      const qtySpans = grid.querySelectorAll(`.quantity-value`)
      const dec = grid.querySelector(`.quantity-btn[data-act="dec"][data-id="${id}"]`)
      const item = ShopState.cart.find(x => String(x.id) === String(id))
      const v = Number(item?.qty || 0)

      // Update all quantity displays for this product
      qtySpans.forEach(span => {
        const parent = span.closest('.product-card')
        if (parent && parent.querySelector(`[data-id="${id}"]`)) {
          span.textContent = v
        }
      })

      if (dec) dec.disabled = v <= 0
    }

    // Buy button handler (adds 1 to cart)
    grid.querySelectorAll('button[data-role="buy"]').forEach(btn => {
      btn.addEventListener('click', () => {
        const id = btn.getAttribute('data-id')
        const prod = this.products.find(x => String(x.id) === String(id))
        if (!prod) return

        // Add to cart
        ShopState.add({ id: prod.id, name: prod.name, price: prod.price, image: prod.image_url || prod.image })

        // Success animation
        btn.classList.add('success')
        setTimeout(() => btn.classList.remove('success'), 600)

        updateQtyUI(id)
      })
    })

    // Increment button
    grid.querySelectorAll('button.quantity-btn[data-act="inc"]').forEach(btn => {
      btn.addEventListener('click', () => {
        const id = btn.getAttribute('data-id')
        const prod = this.products.find(x => String(x.id) === String(id))
        if (!prod) return
        ShopState.add({ id: prod.id, name: prod.name, price: prod.price, image: prod.image_url || prod.image })
        updateQtyUI(id)
      })
    })

    // Decrement button
    grid.querySelectorAll('button.quantity-btn[data-act="dec"]').forEach(btn => {
      btn.addEventListener('click', () => {
        const id = btn.getAttribute('data-id')
        const item = ShopState.cart.find(x => String(x.id) === String(id))
        if (!item) { updateQtyUI(id); return }
        const nextQty = Number(item.qty || 0) - 1
        if (nextQty <= 0) { ShopState.remove(item.id) } else { ShopState.updateQty(item.id, nextQty) }
        updateQtyUI(id)
      })
    })

    // Initialize all quantity displays
    this.products.forEach(p => updateQtyUI(p.id))
  }
}
const MiniCart = {
  update() {
    const countEl = document.getElementById('miniCartCount');
    const subEl = document.getElementById('miniCartSubtotal');
    const cartBadge = document.getElementById('cartBadge');

    if (!countEl || !subEl) return;

    const items = ShopState.cart.reduce((s, i) => s + Number(i.qty || 0), 0);
    countEl.textContent = `${items} item`;
    subEl.textContent = Utils.formatCurrency(ShopState.subtotal());

    // Update cart badge in header
    if (cartBadge) {
      if (items > 0) {
        cartBadge.textContent = items;
        cartBadge.style.display = 'block';
      } else {
        cartBadge.style.display = 'none';
      }
    }
  }
}
const CartPage = {
  async init() {
    // Load tax settings first
    await loadTaxSettings();

    const container = document.getElementById('cartItems'); if (!container) return; const items = ShopState.cart; if (!items.length) { container.innerHTML = `<div class="empty-state"><div class="empty-icon"><i class="fas fa-shopping-cart"></i></div><div class="empty-title">Keranjang kosong</div><div class="empty-description">Ayo tambahkan produk favoritmu ke keranjang</div><a href="index.php" class="btn btn-primary"><i class="fas fa-arrow-left"></i> Lanjut Belanja</a></div>`; CartPage.updateSummary(); return }
    container.innerHTML = items.map(i => `
      <div class="cart-item">
        <img src="${Utils.buildImage(i.image) || ''}" alt="${i.name || ''}" onerror="this.style.display='none'">
        <div class="cart-item-info">
          <div class="cart-item-name">${i.name}</div>
          <div class="quantity-control">
            <button class="quantity-btn" data-act="dec" data-id="${i.id}"><i class="fas fa-minus"></i></button>
            <input type="number" min="1" value="${i.qty}" data-id="${i.id}" class="quantity-input">
            <button class="quantity-btn" data-act="inc" data-id="${i.id}"><i class="fas fa-plus"></i></button>
            <button class="btn btn-ghost btn-sm" data-act="rm" data-id="${i.id}"><i class="fas fa-trash"></i></button>
          </div>
        </div>
        <div class="price text-lg">${Utils.formatCurrency(i.price * i.qty)}</div>
      </div>`).join('')
    container.querySelectorAll('button[data-act]').forEach(b => { b.addEventListener('click', () => { const id = b.getAttribute('data-id'); const act = b.getAttribute('data-act'); const item = ShopState.cart.find(x => String(x.id) === String(id)); if (!item) return; if (act === 'inc') { ShopState.updateQty(item.id, item.qty + 1) } else if (act === 'dec') { ShopState.updateQty(item.id, item.qty - 1) } else if (act === 'rm') { ShopState.remove(item.id) } CartPage.init() }) })
    container.querySelectorAll('input[type=number][data-id]').forEach(inp => { inp.addEventListener('change', () => { const id = inp.getAttribute('data-id'); const v = parseInt(inp.value || '1', 10); ShopState.updateQty(Number(id), v); CartPage.init() }) })

    // Add clear cart functionality
    const clearBtn = document.getElementById('clearCart');
    if (clearBtn) {
      clearBtn.addEventListener('click', () => {
        if (confirm('Apakah Anda yakin ingin mengosongkan keranjang?')) {
          ShopState.cart = [];
          ShopState.save();
          CartPage.init();
          MiniCart.update();
        }
      });
    }

    CartPage.updateSummary()
  },
  updateSummary() {
    const sub = ShopState.subtotal();
    const ship = sub > 0 ? 0 : 0;

    // Calculate tax if enabled
    let tax = 0;
    const taxSettings = window.taxSettings || {};
    if (taxSettings.enabled && taxSettings.rate > 0) {
      tax = sub * (taxSettings.rate / 100);
    }

    const total = sub + ship + tax;

    // Update UI
    const sS = document.getElementById('summarySubtotal');
    const sH = document.getElementById('summaryShipping');
    const sTax = document.getElementById('summaryTax');
    const taxRow = document.getElementById('taxRow');
    const sT = document.getElementById('summaryTotal');

    if (sS) sS.textContent = Utils.formatCurrency(sub);
    if (sH) sH.textContent = Utils.formatCurrency(ship);

    // Show/hide tax row
    if (taxSettings.enabled && tax > 0) {
      if (sTax) sTax.textContent = Utils.formatCurrency(tax);
      if (taxRow) taxRow.style.display = 'flex';
    } else {
      if (taxRow) taxRow.style.display = 'none';
    }

    if (sT) sT.textContent = Utils.formatCurrency(total);
  }
}

// Fungsi validasi form customer
function validateCustomerForm() {
  const name = document.getElementById('customerName')?.value?.trim();
  const phone = document.getElementById('customerPhone')?.value?.trim();
  const address = document.getElementById('customerAddress')?.value?.trim();

  // Validasi nama wajib diisi
  if (!name) {
    return { valid: false, message: 'Nama lengkap wajib diisi' };
  }

  // Validasi nomor HP wajib diisi
  if (!phone) {
    return { valid: false, message: 'Nomor HP wajib diisi untuk pengiriman invoice via WhatsApp' };
  }

  // Validasi format nomor HP (minimal 10 digit)
  if (!/^\d{10,}$/.test(phone.replace(/[^\d]/g, ''))) {
    return { valid: false, message: 'Nomor HP tidak valid (minimal 10 digit)' };
  }

  // Cek apakah COD aktif dan validasi alamat
  const codEnabled = window.codSettings?.enabled || false;
  if (codEnabled && !address) {
    return { valid: false, message: 'Alamat lengkap wajib diisi untuk pengiriman COD' };
  }

  return { valid: true };
}

// Fungsi untuk load COD settings
async function loadCodSettings() {
  try {
    const response = await fetch('../api.php?controller=settings&action=get_public');
    const result = await response.json();
    if (result.success && result.data) {
      window.codSettings = {
        enabled: result.data.cod_enabled === '1' || result.data.cod_enabled === true
      };
      updateAddressRequirement();
    }
  } catch (error) {
    console.error('Failed to load COD settings:', error);
    window.codSettings = { enabled: false };
  }
}

// Fungsi untuk load tax settings
async function loadTaxSettings() {
  try {
    const response = await fetch('../api.php?controller=settings&action=get_public');
    const result = await response.json();
    if (result.success && result.data) {
      // Map shop-specific tax settings from the API response
      window.taxSettings = {
        enabled: result.data.tax_enabled === '1' || result.data.tax_enabled === true,
        rate: parseFloat(result.data.tax_rate) || 11,
        name: result.data.tax_name || 'Pajak'
      };
      // Update tax label if exists
      const taxLabel = document.querySelector('#taxRow .text-gray-600');
      if (taxLabel && window.taxSettings.name) {
        taxLabel.textContent = window.taxSettings.name;
      }
      // Update cart summary
      CartPage.updateSummary();
    }
  } catch (error) {
    console.error('Failed to load tax settings:', error);
    window.taxSettings = { enabled: false, rate: 11, name: 'Pajak' };
  }
}

// Fungsi untuk update tampilan requirement alamat
function updateAddressRequirement() {
  const codEnabled = window.codSettings?.enabled || false;
  const addressLabel = document.getElementById('addressRequired');
  const addressInput = document.getElementById('customerAddress');

  if (addressLabel) {
    if (codEnabled) {
      addressLabel.innerHTML = '<span class="text-danger-500" title="Wajib diisi untuk COD">*</span>';
      if (addressInput) addressInput.required = true;
    } else {
      addressLabel.textContent = '(Opsional)';
      if (addressInput) addressInput.required = false;
    }
  }
}
const CheckoutPage = {
  async init() {
    await loadCodSettings();
    await loadTaxSettings();

    const wrap = document.getElementById('checkoutSummary');
    if (!wrap) return;
    const items = ShopState.cart;
    if (!items.length) {
      wrap.innerHTML = `<div class="empty-state"><div class="empty-icon"><i class="fas fa-shopping-cart"></i></div><div class="empty-title">Keranjang kosong</div><a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left mr-2"></i>Lanjut Belanja</a></div>`;
    } else {
      const sub = ShopState.subtotal();
      const taxSettings = window.taxSettings || { enabled: false, rate: 0, name: 'Pajak' };
      const tax = (taxSettings.enabled && taxSettings.rate > 0) ? (sub * (taxSettings.rate / 100)) : 0;
      const total = sub + tax;
      const rows = items.map((i, idx) => `<div class="summary-row"><span>${idx + 1}. ${i.name} × ${i.qty}</span><span class="price">${Utils.formatCurrency(i.price * i.qty)}</span></div>`).join('');
      const taxRow = (taxSettings.enabled && tax > 0) ? `<div class="summary-row"><span>${taxSettings.name || 'Pajak'}</span><span class="price">${Utils.formatCurrency(tax)}</span></div>` : '';
      wrap.innerHTML = `${rows}<div class="summary-divider"></div><div class="summary-row"><span>Subtotal</span><span class="price">${Utils.formatCurrency(sub)}</span></div>${taxRow}<div class="summary-total"><span>Total</span><span class="price font-bold">${Utils.formatCurrency(total)}</span></div>`;
    }
    const btn = document.getElementById('placeOrderBtn'); if (btn) {
      btn.addEventListener('click', async () => {
        if (!ShopState.cart.length) return;

        // Validasi form
        const validation = validateCustomerForm();
        if (!validation.valid) {
          alert(validation.message);
          return;
        }

        const pay = document.querySelector('input[name="pay"]:checked')?.value || 'transfer'
        const payload = {
          customer_name: document.getElementById('customerName')?.value || '',
          customer_phone: document.getElementById('customerPhone')?.value || '',
          customer_email: document.getElementById('customerEmail')?.value || '',
          customer_address: document.getElementById('customerAddress')?.value || '',
          payment_method: pay,
          items: ShopState.cart.map(i => ({ product_id: i.id, quantity: i.qty }))
        }
        try {
          const r = await Utils.apiCall('?controller=order&action=create', { method: 'POST', body: JSON.stringify(payload) })
          if (r?.success) {
            ShopState.cart = []; ShopState.save();
            PaymentSheet.show(r.data)
          } else {
            const msg = r?.message || 'Gagal membuat pesanan'
            console.error('Order create failed:', r)
            alert(msg)
          }
        } catch (e) {
          console.error('Order create error:', e)
          alert('Terjadi kesalahan jaringan saat membuat pesanan')
        }
      })
    }

    // Persist & restore payment method selection
    const radios = Array.from(document.querySelectorAll('input[name="pay"]'))
    const lastPay = localStorage.getItem('bb_last_pay')
    if (lastPay) {
      const r = radios.find(x => x.value === lastPay && !x.disabled)
      if (r) r.checked = true
    }
    radios.forEach(r => {
      r.addEventListener('change', () => {
        localStorage.setItem('bb_last_pay', r.value)
      })
    })
  }
}
const PaymentSheet = {
  el: null,
  poller: null,
  deadline: null,
  bankSettings: null,

  // Load bank settings from API
  async loadBankSettings() {
    if (this.bankSettings) return this.bankSettings;

    try {
      // Use public endpoint that doesn't require authentication
      const response = await fetch('../api.php?controller=settings&action=get_public_banks');
      const result = await response.json();

      if (result.success && result.data) {
        this.bankSettings = {
          default: result.data.bank_default || 'bca',
          bca: {
            name: result.data.bank_bca_name || 'Bytebalok',
            account: result.data.bank_bca_account || '1234567890'
          },
          bri: {
            name: result.data.bank_bri_name || result.data.bank_mandiri_name || 'Bytebalok',
            account: result.data.bank_bri_account || result.data.bank_mandiri_account || '1234567890'
          },
          blu_bca: {
            name: result.data.bank_blu_bca_name || result.data.bank_bni_name || 'Bytebalok',
            account: result.data.bank_blu_bca_account || result.data.bank_bni_account || '1234567890'
          }
        };
        console.log('Bank settings loaded:', this.bankSettings);
        return this.bankSettings;
      }
    } catch (error) {
      console.error('Failed to load bank settings:', error);
      // Return default settings
      this.bankSettings = {
        default: 'bca',
        bca: { name: 'Bytebalok', account: '1234567890' },
        bri: { name: 'Bytebalok', account: '1234567890' },
        blu_bca: { name: 'Bytebalok', account: '1234567890' }
      };
      console.log('Using default bank settings:', this.bankSettings);
      return this.bankSettings;
    }
  },

  // Get bank display data based on method and payment data
  getBankDisplayData(method, paymentData) {
    const bankKey = method === 'transfer' ? this.bankSettings?.default || 'bca' : method
    const bankInfo = this.bankSettings?.[bankKey] || this.bankSettings?.bca

    return {
      bank_name: paymentData.bank_name || this.getBankDisplayName(bankKey),
      account_name: paymentData.account_name || bankInfo?.name || 'Bytebalok',
      account_number: paymentData.account_number || bankInfo?.account || '1234567890',
      virtual_account: paymentData.virtual_account || '-',
      reference_number: paymentData.reference_number || '-'
    }
  },

  // Get bank display name
  getBankDisplayName(bankKey) {
    const names = {
      'bca': 'Bank BCA',
      'bri': 'Bank BRI',
      'blu_bca': 'BLU BCA',
      'mandiri': 'Bank Mandiri',
      'bni': 'Bank BNI'
    }
    return names[bankKey] || 'Bank Transfer'
  },

  // Create bank info HTML with consistent design
  createBankInfoHTML(bankData) {
    return `
      <div class="bank-info-box">
        <div class="bank-info-header">
          <i class="fas fa-building"></i>
          <span class="bank-name">${bankData.bank_name}</span>
        </div>
        <div class="bank-info-content">
          <div class="bank-info-row">
            <span class="label">Nama Rekening:</span>
            <span class="value">${bankData.account_name}</span>
          </div>
          <div class="bank-info-row">
            <span class="label">Nomor Rekening:</span>
            <span class="value account-number">${bankData.account_number}</span>
          </div>
          ${bankData.virtual_account !== '-' ? `
            <div class="bank-info-row">
              <span class="label">Virtual Account:</span>
              <span class="value">${bankData.virtual_account}</span>
            </div>
          ` : ''}
          ${bankData.reference_number !== '-' ? `
            <div class="bank-info-row">
              <span class="label">Nomor Referensi:</span>
              <span class="value">${bankData.reference_number}</span>
            </div>
          ` : ''}
        </div>
      </div>
    `
  },

  // Payment timer countdown
  paymentTimer: null,
  timeRemaining: 900, // 15 minutes in seconds

  startPaymentTimer() {
    const timerDisplay = document.getElementById('timerDisplay');
    const paymentTimerEl = document.getElementById('paymentTimer');

    if (!timerDisplay) return;

    // Clear existing timer
    if (this.paymentTimer) clearInterval(this.paymentTimer);

    // Reset time
    this.timeRemaining = 900;

    this.paymentTimer = setInterval(() => {
      this.timeRemaining--;

      // Update display
      const minutes = Math.floor(this.timeRemaining / 60);
      const seconds = this.timeRemaining % 60;
      timerDisplay.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;

      // Warning state when < 5 minutes
      if (this.timeRemaining <= 300 && paymentTimerEl) {
        paymentTimerEl.classList.add('warning');
      }

      // Expired
      if (this.timeRemaining <= 0) {
        clearInterval(this.paymentTimer);
        this.showPaymentExpired();
      }
    }, 1000);
  },

  showPaymentExpired() {
    const errorEl = document.getElementById('paymentError');
    const errorMsg = document.getElementById('errorMessage');
    const qrisSection = document.getElementById('qrisSection');
    const transferSection = document.getElementById('transferSection');

    if (errorEl && errorMsg) {
      errorMsg.textContent = 'QR Code telah expired. Silakan buat pesanan baru.';
      errorEl.style.display = 'block';
    }

    // Hide QR/Transfer sections
    if (qrisSection) qrisSection.style.display = 'none';
    if (transferSection) transferSection.style.display = 'none';

    // Show contact support
    const contactBtn = document.getElementById('contactSupportBtn');
    if (contactBtn) contactBtn.style.display = 'block';
  },

  stopPaymentTimer() {
    if (this.paymentTimer) {
      clearInterval(this.paymentTimer);
      this.paymentTimer = null;
    }
  },

  async show(order) {
    console.log('PaymentSheet.show() called with order:', order)
    this.el = this.el || document.getElementById('paymentSheet')
    if (!this.el) return

    // Load bank settings first
    await this.loadBankSettings()

    // Update Order ID and Timestamp
    const orderIdEl = document.getElementById('paymentOrderId');
    const timestampEl = document.getElementById('paymentTimestamp');
    if (orderIdEl && order?.order_number) {
      orderIdEl.textContent = order.order_number;
    }
    if (timestampEl && order?.created_at) {
      const date = new Date(order.created_at);
      const formatted = date.toLocaleString('id-ID', {
        day: '2-digit', month: 'short', year: 'numeric',
        hour: '2-digit', minute: '2-digit'
      });
      timestampEl.textContent = formatted;
    }
    // Setup copy button
    if (typeof window.OrderIdIntegration !== 'undefined') {
      window.OrderIdIntegration.setupCopy();
    }

    // Start payment timer countdown
    this.startPaymentTimer();

    const method = order?.payment_method || order?.payment?.payment_method || ''
    const total = order?.total_amount || 0
    const num = order?.order_number || ''
    const p = order?.payment || {}
    const mLabel = document.getElementById('paymentMethodLabel')
    const qrImg = document.getElementById('qrisImage')
    const transferSection = document.getElementById('transferSection')
    const transferDetails = document.getElementById('transferDetails')
    const meta = document.getElementById('paymentMeta')
    console.log('Payment method:', method)
    console.log('QR image element:', qrImg)
    const sum = document.getElementById('paymentOrderSummary')
    const totalEl = document.getElementById('paymentTotal')
    const link = document.getElementById('trackOrderLink')
    const verifyBtn = document.getElementById('requestVerificationBtn')
    // Insert instruction note near verification button
    try {
      const existingNote = document.getElementById('verificationNote')
      const buttonsWrap = verifyBtn ? verifyBtn.parentElement : document.querySelector('.payment-right .mt-4.space-y-3')
      if (!existingNote && buttonsWrap) {
        const note = document.createElement('div')
        note.id = 'verificationNote'
        note.className = 'text-sm text-gray-600'
        note.innerHTML = '<i class="fas fa-info-circle mr-1"></i> Jika sudah melakukan pembayaran, mohon klik tombol <strong>Verifikasi Pembayaran</strong> agar pesanan segera diproses.'
        buttonsWrap.insertBefore(note, verifyBtn || buttonsWrap.firstChild)
      }
    } catch (e) { }

    // Update payment method with icon
    if (mLabel) {
      const methodIcon = method === 'qris' ? 'fa-qrcode' : 'fa-university'
      mLabel.innerHTML = `<i class="fas ${methodIcon}"></i> ${method === 'qris' ? 'QRIS' : 'Transfer Bank'}`
    }

    // Update payment info based on method
    if (method === 'qris') {
      const qrisSection = document.getElementById('qrisSection')
      if (qrisSection) {
        qrisSection.style.display = 'block'
        console.log('QRIS section displayed')
        console.log('QRIS section display style:', qrisSection.style.display)
        console.log('QRIS section visibility:', window.getComputedStyle(qrisSection).display)
      }
      if (qrImg) {
        console.log('QR image element found:', qrImg)
        console.log('QR image current display:', window.getComputedStyle(qrImg).display)
        console.log('QR image current visibility:', window.getComputedStyle(qrImg).visibility)
        // Set display block dulu sebelum set src
        qrImg.style.display = 'block'
        // Tambahkan delay kecil untuk memastikan DOM siap
        setTimeout(() => {
          // Coba dengan placeholder dulu untuk debugging
          qrImg.src = '../assets/img/placeholder-product.svg';
          console.log('Setting QRIS image src to placeholder for debugging')
          qrImg.onload = () => {
            console.log('Placeholder image loaded successfully')
            // Setelah placeholder berhasil, coba dengan QRIS image
            setTimeout(() => {
              qrImg.src = '/assets/img/qris-gopay.svg';
              qrImg.onerror = function () { this.src = '/assets/img/qris-gopay.svg' }
              console.log('Setting QRIS image src to:', qrImg.src)
            }, 500)
          }
          qrImg.onerror = (e) => console.error('Failed to load image:', e)
        }, 100)
      } else {
        console.error('QR image element not found')
      }
    } else {
      const qrisSection = document.getElementById('qrisSection')
      if (qrisSection) qrisSection.style.display = 'none'
      if (qrImg) qrImg.style.display = 'none'
      if (transferSection) {
        transferSection.style.display = 'block'
        console.log('Transfer section found, displaying bank info')
        // Get bank settings for display
        const bankData = this.getBankDisplayData(method, p)
        console.log('Bank data for display:', bankData)
        if (transferDetails) {
          transferDetails.innerHTML = this.createBankInfoHTML(bankData)
          console.log('Bank info HTML created and inserted')
        } else {
          console.error('Transfer details element not found!')
        }
      } else {
        console.error('Transfer section element not found!')
      }
    }

    // Update expiry info - REMOVED as requested
    if (meta) {
      this.deadline = p?.expired_at ? new Date(p.expired_at) : null
      meta.innerHTML = '' // Clear the expiry info
    }

    // Update summary with improved layout
    if (sum) {
      const items = order?.items || []
      sum.innerHTML = `
        <div class="summary-card">
          <div class="summary-card-header">
            <i class="fas fa-receipt"></i>
            <h3 class="summary-card-title">Ringkasan Order</h3>
          </div>
          <div class="summary-card-body">
            ${items.map((i, idx) => `
              <div class="summary-row">
                <span>${idx + 1}. ${(i.name || i.product_name || 'Item')} × ${i.quantity || i.qty || 1}</span>
                <span>${Utils.formatCurrency(i.total_price || ((i.unit_price || i.price || 0) * (i.quantity || i.qty || 1)))}</span>
              </div>
            `).join('')}
            <div class="summary-row summary-total">
              <span>Total Pembayaran</span>
              <span>${Utils.formatCurrency(total)}</span>
            </div>
          </div>
        </div>
      `
    }

    if (totalEl) totalEl.textContent = Utils.formatCurrency(total)
    if (link) { link.href = `order-status.php?code=${encodeURIComponent(num || '')}` }
    if (num) {
      try { localStorage.setItem('bb_last_order', String(num)) } catch (e) { }
    }
    if (verifyBtn) {
      verifyBtn.disabled = false
      verifyBtn.textContent = 'Verifikasi Pembayaran'
      verifyBtn.onclick = async () => {
        try {
          verifyBtn.disabled = true
          verifyBtn.textContent = 'Mengirim...'
          const resp = await Utils.apiCall(`?controller=order&action=request-verification`, {
            method: 'POST',
            body: JSON.stringify({ order_number: num })
          })
          if (resp && resp.success) {
            if (window.app && app.showToast) app.showToast('Permintaan verifikasi dikirim', 'success')
            verifyBtn.innerHTML = '<i class="fas fa-clock"></i> Menunggu konfirmasi kasir...'
            verifyBtn.style.backgroundColor = '#f59e0b'
            verifyBtn.style.borderColor = '#f59e0b'
            // Show auto-check indicator
            const autoCheckIndicator = document.getElementById('autoCheckIndicator')
            if (autoCheckIndicator) {
              autoCheckIndicator.style.display = 'flex'
            }
            // Start faster polling for auto-redirect (3 seconds)
            this.startAutoCheckPolling(num)
          } else {
            verifyBtn.disabled = false
            verifyBtn.textContent = 'Verifikasi Pembayaran'
            if (window.app && app.showToast) app.showToast('Gagal mengirim verifikasi', 'error')
          }
        } catch (e) {
          verifyBtn.disabled = false
          verifyBtn.textContent = 'Verifikasi Pembayaran'
          if (window.app && app.showToast) app.showToast('Gagal mengirim verifikasi', 'error')
        }
      }
    }
    const changeBtn = document.getElementById('changePaymentMethodBtn')
    if (changeBtn) {
      const payStatus = String(order?.payment_status || p?.status || '').toLowerCase()
      const ordStatus = String(order?.order_status || '').toLowerCase()
      const isPending = (!payStatus || payStatus === 'pending') && (ordStatus !== 'completed' && ordStatus !== 'cancelled')
      changeBtn.style.display = isPending ? 'block' : 'none'
      changeBtn.disabled = false
      changeBtn.onclick = async () => {
        try {
          changeBtn.disabled = true
          changeBtn.textContent = 'Mengubah...'
          const resp = await Utils.apiCall(`?controller=order&action=cancel`, {
            method: 'POST',
            body: JSON.stringify({ order_number: num })
          })
          if (resp && resp.success) {
            const items = (order?.items || []).map(i => ({
              id: i.product_id || i.id,
              name: i.name || i.product_name || 'Item',
              price: Number(i.unit_price || i.price || 0),
              image: i.image || '',
              qty: Number(i.quantity || i.qty || 1)
            }))
            ShopState.cart = items
            ShopState.save()
            this.hide()
            if (window.app && app.showToast) app.showToast('Pesanan dibatalkan. Silakan pilih metode pembayaran lain', 'info')
          } else {
            changeBtn.disabled = false
            changeBtn.textContent = 'Ganti Metode Pembayaran'
            if (window.app && app.showToast) app.showToast('Gagal membatalkan pesanan', 'error')
          }
        } catch (e) {
          changeBtn.disabled = false
          changeBtn.textContent = 'Ganti Metode Pembayaran'
          if (window.app && app.showToast) app.showToast('Gagal membatalkan pesanan', 'error')
        }
      }
    }
    this.el.style.display = 'grid'
    const closeBtn = document.getElementById('closePayment')
    if (closeBtn) { closeBtn.onclick = () => { this.hide() } }
    // Overlay interactions
    this.el.addEventListener('click', (ev) => { if (ev.target && ev.target.id === 'paymentSheet') { this.hide() } })
    document.addEventListener('keydown', this._escHandler)
    // Start polling payment status
    this.startPolling(num)
  },
  hide() {
    if (this.el) this.el.style.display = 'none';
    if (this.poller) { clearInterval(this.poller); this.poller = null }
    this.stopAutoCheckPolling(); // Stop auto-check polling
    this.stopPaymentTimer(); // Stop timer when closing
    document.removeEventListener('keydown', this._escHandler)
  },
  _escHandler(e) { if (e.key === 'Escape') { const ps = document.getElementById('paymentSheet'); if (ps) ps.style.display = 'none' } },
  startPolling(orderNumber) {
    if (!orderNumber) return; if (this.poller) clearInterval(this.poller); this.poller = setInterval(async () => {
      try {
        const r = await Utils.apiCall(`?controller=order&action=check-payment&order_number=${encodeURIComponent(orderNumber)}`)
        const meta = document.getElementById('paymentMeta')
        if (r && r.success) {
          const pay = r.data || {}
          if (meta) {
            // Clear meta info - no expiry or status display as requested
            meta.innerHTML = ''
          }
          const changeBtn = document.getElementById('changePaymentMethodBtn')
          if (changeBtn) {
            const ps = String(pay.payment_status || '').toLowerCase()
            const os = String(pay.order_status || '').toLowerCase()
            const pending = (!ps || ps === 'pending') && (os !== 'completed' && os !== 'cancelled')
            changeBtn.style.display = pending ? 'block' : 'none'
          }
          if (String(pay.payment_status).toLowerCase() === 'success' || String(pay.order_status).toLowerCase() === 'completed') {
            clearInterval(this.poller); this.poller = null
          }
        }
      } catch (e) { }
      // Removed countdown timer as requested
    }, 10000)
  },
  // Auto-check polling for payment acceptance (faster interval)
  autoCheckPoller: null,
  startAutoCheckPolling(orderNumber) {
    if (!orderNumber) return
    // Clear existing auto-check poller
    if (this.autoCheckPoller) clearInterval(this.autoCheckPoller)

    this.autoCheckPoller = setInterval(async () => {
      try {
        const r = await Utils.apiCall(`?controller=payment&action=check-status&order_number=${encodeURIComponent(orderNumber)}`)
        if (r && r.success && r.data) {
          const paymentStatus = String(r.data.payment_status || '').toLowerCase()

          // If payment is accepted/paid, redirect to order status page
          if (paymentStatus === 'paid' || paymentStatus === 'success') {
            // Stop polling
            if (this.autoCheckPoller) {
              clearInterval(this.autoCheckPoller)
              this.autoCheckPoller = null
            }
            if (this.poller) {
              clearInterval(this.poller)
              this.poller = null
            }

            // Show success message
            const autoCheckIndicator = document.getElementById('autoCheckIndicator')
            if (autoCheckIndicator) {
              autoCheckIndicator.innerHTML = '<i class="fas fa-check-circle" style="color: #10b981;"></i> <span>Pembayaran diterima! Mengalihkan...</span>'
            }

            // Show toast notification
            if (window.app && app.showToast) {
              app.showToast('Pembayaran diterima! Mengalihkan ke status pesanan...', 'success')
            }

            // Redirect after short delay for better UX
            setTimeout(() => {
              window.location.href = `order-status.php?code=${encodeURIComponent(orderNumber)}`
            }, 1500)
          }
        }
      } catch (e) {
        console.error('Auto-check polling error:', e)
      }
    }, 3000) // Check every 3 seconds
  },
  stopAutoCheckPolling() {
    if (this.autoCheckPoller) {
      clearInterval(this.autoCheckPoller)
      this.autoCheckPoller = null
    }
  }
}
const CustomerProgress = {
  el: null,
  code: null,
  poller: null,
  init() {
    try { this.code = localStorage.getItem('bb_last_order') || null } catch (e) { this.code = null }
    if (!this.code) return
    this.ensureBanner()
    this.start()
  },
  ensureBanner() {
    this.el = document.getElementById('customerProgressBanner')
    if (this.el) return
    const div = document.createElement('div')
    div.id = 'customerProgressBanner'
    div.className = 'progress-banner'
    div.innerHTML = `
      <div class="progress-card">
        <div class="progress-header">
          <i class="fas fa-clipboard-check"></i>
          <span>Progres Pesanan</span>
          <button class="progress-close" aria-label="Tutup"><i class="fas fa-times"></i></button>
        </div>
        <div class="progress-body">
          <div class="progress-steps">
            <div class="step" data-step="pending"><span class="dot"></span><span class="label">Dibuat</span></div>
            <div class="step" data-step="processing"><span class="dot"></span><span class="label">Diproses</span></div>
            <div class="step" data-step="ready"><span class="dot"></span><span class="label">Siap</span></div>
            <div class="step" data-step="completed"><span class="dot"></span><span class="label">Selesai</span></div>
          </div>
          <div class="progress-actions">
            <button class="btn btn-outline" id="copyOrderCode"><i class="fas fa-copy mr-2"></i>Salin Order #</button>
            <a class="btn btn-primary" id="openTrackLink" target="_blank"><i class="fas fa-search mr-2"></i>Lihat Status</a>
          </div>
        </div>
      </div>`
    document.body.appendChild(div)
    const closeBtn = div.querySelector('.progress-close')
    if (closeBtn) closeBtn.addEventListener('click', () => { div.style.display = 'none' })
    const copyBtn = div.querySelector('#copyOrderCode')
    if (copyBtn) copyBtn.addEventListener('click', async () => {
      try { await navigator.clipboard.writeText(this.code || ''); if (window.app && app.showToast) app.showToast('Order number disalin', 'success') } catch (e) { }
    })
    const trackLink = div.querySelector('#openTrackLink')
    if (trackLink) trackLink.href = `order-status.php?code=${encodeURIComponent(this.code || '')}`
  },
  update(status) {
    if (!this.el) return
    const st = String(status || 'pending').toLowerCase()
    const steps = ['pending', 'processing', 'ready', 'completed']
    steps.forEach(s => {
      const node = this.el.querySelector(`.step[data-step="${s}"]`)
      if (!node) return
      node.classList.toggle('active', s === st)
      node.classList.toggle('done', steps.indexOf(s) < steps.indexOf(st))
    })
    this.el.style.display = 'block'
  },
  start() {
    if (!this.code) return
    if (this.poller) clearInterval(this.poller)
    const tick = async () => {
      try {
        const r = await Utils.apiCall(`?controller=order&action=check-payment&order_number=${encodeURIComponent(this.code)}`)
        if (r && r.success) {
          const st = r.data?.order_status || 'pending'
          this.update(st)
          if (String(st).toLowerCase() === 'completed' || String(st).toLowerCase() === 'cancelled') {
            clearInterval(this.poller); this.poller = null
            try { localStorage.removeItem('bb_last_order') } catch (e) { }
          }
        }
      } catch (e) { }
    }
    tick()
    this.poller = setInterval(tick, 8000)
  }
}
const TrackOrderPage = {
  init() {
    const form = document.getElementById('trackOrderForm');
    if (!form) return;

    const input = document.getElementById('trackOrderNumber');
    const result = document.getElementById('orderStatusResult');
    const loadingEl = document.getElementById('trackLoading');

    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const code = input.value.trim();
      if (!code) return;

      // Show loading state
      if (loadingEl) {
        loadingEl.style.display = 'block';
        loadingEl.innerHTML = '<div class="spinner"></div>';
      }
      result.style.display = 'none';

      try {
        const r = await Utils.apiCall(`?controller=order&action=get-by-number&order_number=${encodeURIComponent(code)}`);

        if (loadingEl) loadingEl.style.display = 'none';

        if (r?.success) {
          const d = r.data || {};
          result.style.display = 'block';
          result.innerHTML = TrackOrderPage.renderStatusCard(d, code);
          TrackOrderPage.startPolling(code, result);
        } else {
          result.style.display = 'block';
          result.innerHTML = `
            <div class="empty-state">
              <div class="empty-icon"><i class="fas fa-search"></i></div>
              <div class="empty-title">Pesanan tidak ditemukan</div>
              <div class="empty-description">Pastikan nomor order yang Anda masukkan benar</div>
            </div>
          `;
        }
      } catch (e) {
        if (loadingEl) loadingEl.style.display = 'none';
        result.style.display = 'block';
        result.innerHTML = `
          <div class="empty-state">
            <div class="empty-icon"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="empty-title">Gagal mengambil status pesanan</div>
            <div class="empty-description">Silakan coba lagi nanti</div>
          </div>
        `;
      }
    });
    // Auto-submit if code provided via URL
    try {
      const preset = (input && input.value || '').trim()
      if (preset) {
        setTimeout(() => { form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true })) }, 50)
      }
    } catch (e) { }
  },
  renderStatusCard(d, code) {
    const steps = [
      { key: 'pending', title: 'Pesanan Dibuat', sub: 'Menunggu diproses', icon: 'clock' },
      { key: 'processing', title: 'Diproses', sub: 'Sedang dipersiapkan', icon: 'tools' },
      { key: 'ready', title: 'Siap', sub: 'Siap diambil/dikirim', icon: 'box-open' },
      { key: 'completed', title: 'Selesai', sub: 'Pesanan selesai', icon: 'check-circle' }
    ];
    const status = String(d.order_status || 'pending').toLowerCase();
    const idx = steps.findIndex(s => s.key === status);
    const activeIdx = idx >= 0 ? idx : 0;
    const progress = `
      <div class="order-progress">
        ${steps.map((s, i) => `
          <div class="order-step ${i < activeIdx ? 'done' : ''} ${i === activeIdx ? 'active' : ''}">
            <div class="step-icon"><i class="fas fa-${s.icon}"></i></div>
            <div class="step-title">${s.title}</div>
            <div class="step-sub">${s.sub}</div>
          </div>
        `).join('')}
      </div>`;
    const itemsHtml = (d.items && d.items.length) ? `
      <div class="mt-4">
        <h4 class="text-sm font-semibold text-gray-700 mb-2">Detail Items</h4>
        <div class="space-y-2">
          ${d.items.map((item) => `
            <div class="flex justify-between items-center py-2 border-b border-gray-100 last:border-b-0">
              <div class="flex-1">
                <div class="font-medium text-sm">${item.name || item.product_name || 'Item'}</div>
                <div class="text-xs text-gray-500">${item.quantity || item.qty || 1} × ${Utils.formatCurrency(item.unit_price || item.price || 0)}</div>
              </div>
              <div class="font-semibold text-sm">${Utils.formatCurrency(item.total_price || ((item.unit_price || item.price || 0) * (item.quantity || item.qty || 1)))}</div>
            </div>
          `).join('')}
        </div>
      </div>`: '';
    return `
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">Order #${d.order_number || code}</h3>
          <span class="badge badge-${status === 'completed' ? 'success' : status === 'cancelled' ? 'error' : status === 'ready' ? 'primary' : status === 'processing' ? 'info' : 'warning'}">${d.order_status || '-'}</span>
        </div>
        <div class="card-body">
          ${progress}
          <div class="info-grid">
            <div class="info-item"><span class="info-label">Status Order</span><span class="info-value">${d.order_status || '-'}</span></div>
            <div class="info-item"><span class="info-label">Status Pembayaran</span><span class="info-value">${d.payment_status || '-'}</span></div>
            <div class="info-item"><span class="info-label">Total</span><span class="info-value font-bold">${Utils.formatCurrency(d.total_amount || 0)}</span></div>
            ${d.created_at ? `<div class="info-item"><span class="info-label">Tanggal</span><span class="info-value">${new Date(d.created_at).toLocaleDateString('id-ID')}</span></div>` : ''}
          </div>
          ${itemsHtml}
        </div>
      </div>`;
  },
  startPolling(code, container) {
    if (!code) return;
    const intervalMs = 5000;
    if (this._poller) clearInterval(this._poller);
    this._poller = setInterval(async () => {
      try {
        const r = await Utils.apiCall(`?controller=order&action=get-by-number&order_number=${encodeURIComponent(code)}`);
        if (r?.success) {
          const d = r.data || {};
          if (container) { container.innerHTML = TrackOrderPage.renderStatusCard(d, code); container.classList.add('status-refresh'); setTimeout(() => container.classList.remove('status-refresh'), 260) }
          const st = String(d.order_status || '').toLowerCase();
          if (st === 'completed' || st === 'cancelled') { clearInterval(this._poller); this._poller = null; }
        }
      } catch (e) { }
    }, intervalMs);
  }
}
document.addEventListener('DOMContentLoaded', () => {
  (function () { const w = window.innerWidth; document.documentElement.classList.add('density-compact') })()
  const header = document.querySelector('.shop-header');
  if (header) {
    const setOffset = () => { document.documentElement.style.setProperty('--sticky-offset', header.offsetHeight + 'px') }
    setOffset()
    window.addEventListener('resize', setOffset, { passive: true })
  }
  const path = window.location.pathname
  if (path.includes('/shop/') || path.endsWith('/shop')) { Catalog.init() }
  if (path.includes('cart.php')) { CartPage.init() }
  if (path.includes('checkout.php')) { CheckoutPage.init() }
  if (path.includes('order-status.php')) { TrackOrderPage.init() }
  CustomerProgress.init()
})
