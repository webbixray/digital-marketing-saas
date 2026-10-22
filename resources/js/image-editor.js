/**
 * Image Editor Component for Media Library v7.0
 * Alpine.js + Canvas API
 */
document.addEventListener('alpine:init', () => {
    Alpine.data('imageEditor', () => ({
        canvas: null,
        ctx: null,
        originalImage: null,
        imageUrl: '',
        imageName: '',

        // State
        history: [],
        historyIndex: -1,
        maxHistory: 30,
        isLoading: false,
        isSaving: false,

        // Tool state
        activeTool: 'none',
        isDragging: false,
        dragStart: { x: 0, y: 0 },
        cropRect: null,

        // Adjustments
        adjustments: {
            brightness: 100,
            contrast: 100,
            saturation: 100,
            grayscale: 0,
            sepia: 0,
            blur: 0,
        },

        // Transform state
        rotation: 0,
        flipH: false,
        flipV: false,
        scale: 100,

        // Resize
        resizeWidth: 0,
        resizeHeight: 0,
        maintainAspectRatio: true,

        init() {
            this.canvas = this.$refs.editorCanvas;
            this.ctx = this.canvas.getContext('2d');
            this.imageUrl = this.$refs.editorCanvas.dataset.imageUrl;
            this.imageName = this.$refs.editorCanvas.dataset.imageName;

            this.loadImage();
            this.bindKeyboardShortcuts();
        },

        loadImage() {
            if (!this.imageUrl) return;

            this.isLoading = true;
            const img = new Image();
            img.crossOrigin = 'anonymous';
            img.onload = () => {
                this.originalImage = img;
                this.resizeWidth = img.width;
                this.resizeHeight = img.height;
                this.canvas.width = img.width;
                this.canvas.height = img.height;
                this.renderImage();
                this.pushHistory();
                this.isLoading = false;
            };
            img.onerror = () => {
                alert('Failed to load image.');
                this.isLoading = false;
            };
            img.src = this.imageUrl;
        },

        renderImage() {
            if (!this.originalImage) return;

            const img = this.originalImage;
            const w = this.canvas.width;
            const h = this.canvas.height;

            this.ctx.clearRect(0, 0, w, h);
            this.ctx.save();

            // Apply transforms
            this.ctx.translate(w / 2, h / 2);
            this.ctx.rotate((this.rotation * Math.PI) / 180);
            this.ctx.scale(this.flipH ? -1 : 1, this.flipV ? -1 : 1);
            this.ctx.translate(-w / 2, -h / 2);

            // Apply filters
            const filters = this.buildFilterString();
            if (filters) {
                this.ctx.filter = filters;
            }

            this.ctx.drawImage(img, 0, 0, w, h);
            this.ctx.restore();

            // Draw crop overlay
            if (this.activeTool === 'crop' && this.cropRect) {
                this.drawCropOverlay();
            }
        },

        buildFilterString() {
            const parts = [];
            if (this.adjustments.brightness !== 100) {
                parts.push(`brightness(${this.adjustments.brightness}%)`);
            }
            if (this.adjustments.contrast !== 100) {
                parts.push(`contrast(${this.adjustments.contrast}%)`);
            }
            if (this.adjustments.saturation !== 100) {
                parts.push(`saturate(${this.adjustments.saturation}%)`);
            }
            if (this.adjustments.grayscale > 0) {
                parts.push(`grayscale(${this.adjustments.grayscale}%)`);
            }
            if (this.adjustments.sepia > 0) {
                parts.push(`sepia(${this.adjustments.sepia}%)`);
            }
            if (this.adjustments.blur > 0) {
                parts.push(`blur(${this.adjustments.blur}px)`);
            }
            return parts.length > 0 ? parts.join(' ') : '';
        },

        drawCropOverlay() {
            const { x, y, width, height } = this.cropRect;
            const w = this.canvas.width;
            const h = this.canvas.height;

            this.ctx.save();
            this.ctx.fillStyle = 'rgba(0, 0, 0, 0.5)';
            this.ctx.fillRect(0, 0, w, y);
            this.ctx.fillRect(0, y, x, height);
            this.ctx.fillRect(x + width, y, w - x - width, height);
            this.ctx.fillRect(0, y + height, w, h - y - height);

            this.ctx.strokeStyle = '#ffffff';
            this.ctx.lineWidth = 2;
            this.ctx.setLineDash([5, 5]);
            this.ctx.strokeRect(x, y, width, height);
            this.ctx.restore();
        },

        // Crop tool
        startCrop() {
            this.activeTool = 'crop';
            this.cropRect = null;
            this.renderImage();
        },

        onMouseDown(e) {
            if (this.activeTool !== 'crop') return;

            const rect = this.canvas.getBoundingClientRect();
            const scaleX = this.canvas.width / rect.width;
            const scaleY = this.canvas.height / rect.height;

            this.isDragging = true;
            this.dragStart = {
                x: (e.clientX - rect.left) * scaleX,
                y: (e.clientY - rect.top) * scaleY,
            };
        },

        onMouseMove(e) {
            if (!this.isDragging || this.activeTool !== 'crop') return;

            const rect = this.canvas.getBoundingClientRect();
            const scaleX = this.canvas.width / rect.width;
            const scaleY = this.canvas.height / rect.height;

            const x = (e.clientX - rect.left) * scaleX;
            const y = (e.clientY - rect.top) * scaleY;

            this.cropRect = {
                x: Math.min(this.dragStart.x, x),
                y: Math.min(this.dragStart.y, y),
                width: Math.abs(x - this.dragStart.x),
                height: Math.abs(y - this.dragStart.y),
            };

            this.renderImage();
        },

        onMouseUp() {
            if (!this.isDragging) return;
            this.isDragging = false;
        },

        applyCrop() {
            if (!this.cropRect || this.cropRect.width < 10 || this.cropRect.height < 10) {
                return;
            }

            const { x, y, width, height } = this.cropRect;
            const tempCanvas = document.createElement('canvas');
            tempCanvas.width = width;
            tempCanvas.height = height;
            const tempCtx = tempCanvas.getContext('2d');
            tempCtx.drawImage(this.canvas, x, y, width, height, 0, 0, width, height);

            const newImg = new Image();
            newImg.onload = () => {
                this.originalImage = newImg;
                this.canvas.width = width;
                this.canvas.height = height;
                this.resizeWidth = width;
                this.resizeHeight = height;
                this.activeTool = 'none';
                this.cropRect = null;
                this.renderImage();
                this.pushHistory();
            };
            newImg.src = tempCanvas.toDataURL();
        },

        cancelCrop() {
            this.activeTool = 'none';
            this.cropRect = null;
            this.renderImage();
        },

        // Resize
        applyResize() {
            const newWidth = parseInt(this.resizeWidth);
            const newHeight = parseInt(this.resizeHeight);

            if (newWidth < 1 || newHeight < 1 || newWidth > 10000 || newHeight > 10000) {
                return;
            }

            const tempCanvas = document.createElement('canvas');
            tempCanvas.width = newWidth;
            tempCanvas.height = newHeight;
            const tempCtx = tempCanvas.getContext('2d');
            tempCtx.drawImage(this.originalImage, 0, 0, newWidth, newHeight);

            const newImg = new Image();
            newImg.onload = () => {
                this.originalImage = newImg;
                this.canvas.width = newWidth;
                this.canvas.height = newHeight;
                this.renderImage();
                this.pushHistory();
            };
            newImg.src = tempCanvas.toDataURL();
        },

        onResizeWidthChange() {
            if (this.maintainAspectRatio && this.originalImage) {
                const ratio = this.originalImage.height / this.originalImage.width;
                this.resizeHeight = Math.round(this.resizeWidth * ratio);
            }
        },

        onResizeHeightChange() {
            if (this.maintainAspectRatio && this.originalImage) {
                const ratio = this.originalImage.width / this.originalImage.height;
                this.resizeWidth = Math.round(this.resizeHeight * ratio);
            }
        },

        // Rotate
        rotate(degrees) {
            this.rotation = (this.rotation + degrees) % 360;

            if (degrees === 90 || degrees === -90) {
                const newWidth = this.canvas.height;
                const newHeight = this.canvas.width;
                this.canvas.width = newWidth;
                this.canvas.height = newHeight;
                this.resizeWidth = newWidth;
                this.resizeHeight = newHeight;
            }

            this.renderImage();
            this.pushHistory();
        },

        // Flip
        flipHorizontal() {
            this.flipH = !this.flipH;
            this.renderImage();
            this.pushHistory();
        },

        flipVertical() {
            this.flipV = !this.flipV;
            this.renderImage();
            this.pushHistory();
        },

        // Filter adjustments
        applyAdjustments() {
            this.renderImage();
        },

        commitAdjustments() {
            this.pushHistory();
        },

        resetAdjustments() {
            this.adjustments = {
                brightness: 100,
                contrast: 100,
                saturation: 100,
                grayscale: 0,
                sepia: 0,
                blur: 0,
            };
            this.renderImage();
        },

        // History
        pushHistory() {
            const snapshot = this.canvas.toDataURL();

            // Remove future history if we're not at the end
            if (this.historyIndex < this.history.length - 1) {
                this.history = this.history.slice(0, this.historyIndex + 1);
            }

            // Remove oldest if at max
            if (this.history.length >= this.maxHistory) {
                this.history.shift();
            }

            this.history.push(snapshot);
            this.historyIndex = this.history.length - 1;
        },

        undo() {
            if (this.historyIndex <= 0) return;

            this.historyIndex--;
            this.restoreFromHistory();
        },

        redo() {
            if (this.historyIndex >= this.history.length - 1) return;

            this.historyIndex++;
            this.restoreFromHistory();
        },

        restoreFromHistory() {
            const snapshot = this.history[this.historyIndex];
            const img = new Image();
            img.onload = () => {
                this.originalImage = img;
                this.canvas.width = img.width;
                this.canvas.height = img.height;
                this.resizeWidth = img.width;
                this.resizeHeight = img.height;
                this.rotation = 0;
                this.flipH = false;
                this.flipV = false;
                this.resetAdjustments();
                this.renderImage();
            };
            img.src = snapshot;
        },

        // Reset everything
        resetAll() {
            this.rotation = 0;
            this.flipH = false;
            this.flipV = false;
            this.scale = 100;
            this.resetAdjustments();

            const img = new Image();
            img.crossOrigin = 'anonymous';
            img.onload = () => {
                this.originalImage = img;
                this.canvas.width = img.width;
                this.canvas.height = img.height;
                this.resizeWidth = img.width;
                this.resizeHeight = img.height;
                this.renderImage();
                this.history = [];
                this.historyIndex = -1;
                this.pushHistory();
            };
            img.src = this.imageUrl;
        },

        // Save / Download
        downloadImage() {
            const link = document.createElement('a');
            link.download = 'edited-' + this.imageName;
            link.href = this.canvas.toDataURL('image/png');
            link.click();
        },

        saveImage() {
            this.isSaving = true;
            const imageData = this.canvas.toDataURL('image/jpeg', 0.9);

            fetch(window.location.pathname, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ image_data: imageData }),
            })
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    alert('Image saved successfully!');
                } else {
                    alert(data.message || 'Failed to save image.');
                }
            })
            .catch((error) => {
                console.error('Save error:', error);
                alert('An error occurred while saving.');
            })
            .finally(() => {
                this.isSaving = false;
            });
        },

        // Keyboard shortcuts
        bindKeyboardShortcuts() {
            document.addEventListener('keydown', (e) => {
                // Don't trigger shortcuts when typing in inputs
                if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
                    return;
                }

                const ctrl = e.ctrlKey || e.metaKey;

                if (ctrl && e.key === 'z') {
                    e.preventDefault();
                    this.undo();
                } else if (ctrl && e.key === 'y') {
                    e.preventDefault();
                    this.redo();
                } else if (ctrl && e.key === 's') {
                    e.preventDefault();
                    this.saveImage();
                } else if (e.key === 'Escape') {
                    if (this.activeTool === 'crop') {
                        this.cancelCrop();
                    }
                } else if (e.key === 'r' && !ctrl) {
                    this.rotate(90);
                } else if (e.key === 'h' && !ctrl) {
                    this.flipHorizontal();
                } else if (e.key === 'v' && !ctrl) {
                    this.flipVertical();
                }
            });
        },
    }));
});
