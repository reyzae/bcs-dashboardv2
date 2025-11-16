/**
 * Product Image Upload Handler
 * Handles drag & drop, preview, upload, and image management
 */

class ProductImageUpload {
    constructor() {
        this.imageInput = document.getElementById('productImageInput');
        this.dropZone = document.getElementById('imageDropZone');
        this.previewArea = document.getElementById('imagePreviewArea');
        this.preview = document.getElementById('productImagePreview');
        this.selectBtn = document.getElementById('selectImageBtn');
        this.replaceBtn = document.getElementById('replaceImageBtn');
        this.deleteBtn = document.getElementById('deleteImageBtn');
        this.zoomBtn = document.getElementById('zoomImageBtn');
        this.uploadProgress = document.getElementById('uploadProgress');
        this.progressBar = document.getElementById('uploadProgressBar');
        this.progressText = document.getElementById('uploadProgressText');
        
        this.selectedFile = null;
        this.currentImagePath = null;
        this.maxFileSize = 5 * 1024 * 1024; // 5MB
        this.allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        
        this.init();
    }
    
    init() {
        // Select/Browse button
        this.selectBtn?.addEventListener('click', () => this.imageInput.click());
        
        // Drop zone click
        this.dropZone?.addEventListener('click', () => this.imageInput.click());
        
        // File input change
        this.imageInput?.addEventListener('change', (e) => this.handleFileSelect(e));
        
        // Drag & drop events
        this.dropZone?.addEventListener('dragover', (e) => this.handleDragOver(e));
        this.dropZone?.addEventListener('dragleave', (e) => this.handleDragLeave(e));
        this.dropZone?.addEventListener('drop', (e) => this.handleDrop(e));
        
        // Action buttons
        this.replaceBtn?.addEventListener('click', () => this.imageInput.click());
        this.deleteBtn?.addEventListener('click', () => this.deleteImage(true, true));
        this.zoomBtn?.addEventListener('click', () => this.zoomImage());
        
        console.log('✅ Product Image Upload initialized');
    }
    
    handleDragOver(e) {
        e.preventDefault();
        e.stopPropagation();
        this.dropZone.classList.add('drag-over');
    }
    
    handleDragLeave(e) {
        e.preventDefault();
        e.stopPropagation();
        this.dropZone.classList.remove('drag-over');
    }
    
    handleDrop(e) {
        e.preventDefault();
        e.stopPropagation();
        this.dropZone.classList.remove('drag-over');
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            this.processFile(files[0]);
        }
    }
    
    handleFileSelect(e) {
        const files = e.target.files;
        if (files.length > 0) {
            this.processFile(files[0]);
        }
    }
    
    processFile(file) {
        console.log('📂 Processing file:', file.name, file.type, file.size);
        
        // Validate file
        if (!this.validateFile(file)) {
            console.warn('❌ File validation failed');
            return;
        }
        
        this.selectedFile = file;
        console.log('✅ File selected and stored:', this.selectedFile.name);
        
        // Show preview
        this.showPreview(file);
        
        // Show file info
        this.showFileInfo(file);
    }
    
    validateFile(file) {
        // Check file type
        if (!this.allowedTypes.includes(file.type)) {
            if (window.app) {
                app.showToast('Invalid file type. Please use JPG, PNG, or WEBP', 'error');
            } else {
                alert('Invalid file type. Please use JPG, PNG, or WEBP');
            }
            return false;
        }
        
        // Check file size
        if (file.size > this.maxFileSize) {
            const sizeMB = (this.maxFileSize / 1024 / 1024).toFixed(0);
            if (window.app) {
                app.showToast(`File too large. Maximum size is ${sizeMB}MB`, 'error');
            } else {
                alert(`File too large. Maximum size is ${sizeMB}MB`);
            }
            return false;
        }
        
        return true;
    }
    
    showPreview(file) {
        const reader = new FileReader();
        
        reader.onload = (e) => {
            this.preview.src = e.target.result;
            
            // Hide drop zone, show preview
            this.dropZone.style.display = 'none';
            this.previewArea.style.display = 'block';
            
            // Get image dimensions
            const img = new Image();
            img.onload = () => {
                document.getElementById('imageDimensions').textContent = `${img.width}x${img.height}`;
            };
            img.src = e.target.result;
        };
        
        reader.readAsDataURL(file);
    }
    
    showFileInfo(file) {
        // File name
        const fileName = file.name.length > 20 ? file.name.substring(0, 20) + '...' : file.name;
        document.getElementById('imageFileName').textContent = fileName;
        
        // File size
        const sizeKB = (file.size / 1024).toFixed(2);
        const sizeMB = (file.size / 1024 / 1024).toFixed(2);
        const sizeText = file.size > 1024 * 1024 ? `${sizeMB} MB` : `${sizeKB} KB`;
        document.getElementById('imageFileSize').textContent = sizeText;
    }
    
    deleteImage(silent = false, notify = true) {
        if (!silent) {
            if (!confirm('Remove this image?')) {
                return;
            }
        }
        
        // Clear selected file
        this.selectedFile = null;
        this.currentImagePath = null;
        this.imageInput.value = '';
        
        // Hide preview, show drop zone
        this.previewArea.style.display = 'none';
        this.dropZone.style.display = 'block';
        
        if (notify && window.app) {
            app.showToast('Image removed', 'info');
        }
    }
    
    zoomImage() {
        // Create zoom modal
        const modal = document.createElement('div');
        modal.className = 'image-zoom-modal show';
        modal.innerHTML = `<img src="${this.preview.src}" alt="Product Image">`;
        
        // Close on click
        modal.addEventListener('click', () => {
            modal.classList.remove('show');
            setTimeout(() => modal.remove(), 300);
        });
        
        document.body.appendChild(modal);
    }
    
    async uploadImage() {
        console.log('📤 uploadImage() called - selectedFile:', this.selectedFile);
        
        if (!this.selectedFile) {
            console.warn('⚠️ No file selected, returning null');
            return null;
        }
        
        const formData = new FormData();
        formData.append('image', this.selectedFile);
        
        console.log('🚀 Uploading to API:', this.selectedFile.name);
        
        try {
            this.showProgress(0);
            
            const response = await fetch('../api.php?controller=product&action=uploadImage', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            console.log('📦 Upload API response:', result);
            
            this.hideProgress();
            
            if (result.success) {
                this.currentImagePath = result.data.path || result.data.filename;
                console.log('💾 Image uploaded! Path saved:', this.currentImagePath);
                if (window.app) {
                    app.showToast('Image uploaded successfully', 'success');
                }
                // Return the full path
                return this.currentImagePath;
            } else {
                throw new Error(result.message || 'Upload failed');
            }
        } catch (error) {
            this.hideProgress();
            console.error('❌ Upload error:', error);
            if (window.app) {
                app.showToast('Failed to upload image: ' + error.message, 'error');
            }
            return null;
        }
    }
    
    showProgress(percent) {
        this.uploadProgress.style.display = 'block';
        this.progressBar.style.width = percent + '%';
        this.progressText.textContent = Math.round(percent) + '%';
    }
    
    hideProgress() {
        setTimeout(() => {
            this.uploadProgress.style.display = 'none';
            this.progressBar.style.width = '0%';
        }, 500);
    }
    
    loadExistingImage(imagePath) {
        if (!imagePath) return;
        
        // Normalize: if path already includes 'uploads/', use as-is; otherwise treat as filename
        const normalizedPath = imagePath.includes('uploads/') ? imagePath : ('uploads/products/' + imagePath);
        this.currentImagePath = normalizedPath;
        this.preview.src = '../' + normalizedPath;
        this.dropZone.style.display = 'none';
        this.previewArea.style.display = 'block';
        
        // Set file info (if available)
        document.getElementById('imageFileName').textContent = normalizedPath.split('/').pop();
    }
    
    getImagePath() {
        return this.currentImagePath;
    }
    
    hasImage() {
        const hasFile = this.selectedFile !== null;
        const hasPath = this.currentImagePath !== null;
        const result = hasFile || hasPath;
        
        console.log('🔍 hasImage() check:', {
            selectedFile: this.selectedFile ? this.selectedFile.name : 'null',
            currentImagePath: this.currentImagePath,
            hasFile: hasFile,
            hasPath: hasPath,
            result: result
        });
        
        return result;
    }
}

// Initialize on page load and make it globally accessible
window.productImageUpload = null;

document.addEventListener('DOMContentLoaded', function() {
    window.productImageUpload = new ProductImageUpload();
    console.log('✅ window.productImageUpload initialized:', window.productImageUpload);
});

