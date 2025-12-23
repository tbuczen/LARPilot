import { Controller } from '@hotwired/stimulus';
import L from 'leaflet';
import 'leaflet/dist/leaflet.min.css';

/**
 * Map Configuration Preview Controller
 *
 * Provides real-time preview of map image with configurable grid overlay.
 * Allows users to see changes before saving.
 */
export default class extends Controller {
    static targets = [
        'preview',      // The map container div
        'fileInput',    // File input for new image
        'gridRows',     // Grid rows input
        'gridColumns',  // Grid columns input
        'gridOpacity',  // Grid opacity input
        'gridVisible',  // Grid visibility checkbox
        'placeholder'   // Placeholder shown when no image
    ];

    static values = {
        existingImage: String,  // URL of existing image (for edit mode)
        gridRows: { type: Number, default: 10 },
        gridColumns: { type: Number, default: 10 },
        gridOpacity: { type: Number, default: 0.5 },
        gridVisible: { type: Boolean, default: true }
    };

    connect() {
        this.map = null;
        this.gridLayer = null;
        this.imageOverlay = null;
        this.blobUrl = null;

        // Initialize with existing image if available
        if (this.existingImageValue) {
            this.loadImage(this.existingImageValue);
        }

        // Sync initial values from form inputs
        this.syncFromInputs();
    }

    disconnect() {
        this.cleanup();
    }

    cleanup() {
        if (this.map) {
            this.map.remove();
            this.map = null;
        }
        if (this.blobUrl) {
            URL.revokeObjectURL(this.blobUrl);
            this.blobUrl = null;
        }
    }

    /**
     * Sync grid values from form inputs
     */
    syncFromInputs() {
        if (this.hasGridRowsTarget) {
            this.gridRowsValue = parseInt(this.gridRowsTarget.value) || 10;
        }
        if (this.hasGridColumnsTarget) {
            this.gridColumnsValue = parseInt(this.gridColumnsTarget.value) || 10;
        }
        if (this.hasGridOpacityTarget) {
            this.gridOpacityValue = parseFloat(this.gridOpacityTarget.value) || 0.5;
        }
        if (this.hasGridVisibleTarget) {
            this.gridVisibleValue = this.gridVisibleTarget.checked;
        }
    }

    /**
     * Handle file input change - create blob URL and load preview
     */
    onFileChange(event) {
        const file = event.target.files[0];
        if (!file) return;

        // Validate file type
        if (!file.type.startsWith('image/')) {
            console.warn('Selected file is not an image');
            return;
        }

        // Revoke previous blob URL
        if (this.blobUrl) {
            URL.revokeObjectURL(this.blobUrl);
        }

        // Create new blob URL for preview
        this.blobUrl = URL.createObjectURL(file);
        this.loadImage(this.blobUrl);
    }

    /**
     * Handle grid rows input change
     */
    onGridRowsChange(event) {
        const value = parseInt(event.target.value);
        if (value > 0 && value <= 100) {
            this.gridRowsValue = value;
            this.redrawGrid();
        }
    }

    /**
     * Handle grid columns input change
     */
    onGridColumnsChange(event) {
        const value = parseInt(event.target.value);
        if (value > 0 && value <= 100) {
            this.gridColumnsValue = value;
            this.redrawGrid();
        }
    }

    /**
     * Handle grid opacity input change
     */
    onGridOpacityChange(event) {
        const value = parseFloat(event.target.value);
        if (value >= 0 && value <= 1) {
            this.gridOpacityValue = value;
            this.redrawGrid();
        }
    }

    /**
     * Handle grid visibility checkbox change
     */
    onGridVisibleChange(event) {
        this.gridVisibleValue = event.target.checked;
        this.redrawGrid();
    }

    /**
     * Load image and initialize map
     */
    loadImage(imageUrl) {
        // Clean up existing map
        if (this.map) {
            this.map.remove();
            this.map = null;
        }

        // Show preview, hide placeholder
        if (this.hasPlaceholderTarget) {
            this.placeholderTarget.classList.add('d-none');
        }
        if (this.hasPreviewTarget) {
            this.previewTarget.classList.remove('d-none');
        }

        const img = new Image();
        img.src = imageUrl;

        img.onload = () => {
            this.imageWidth = img.width;
            this.imageHeight = img.height;

            const bounds = [[0, 0], [img.height, img.width]];

            // Initialize Leaflet map with simple CRS for image
            this.map = L.map(this.previewTarget, {
                crs: L.CRS.Simple,
                minZoom: -3,
                maxZoom: 2,
                center: [img.height / 2, img.width / 2],
                zoom: -1,
                attributionControl: false
            });

            // Add image overlay
            this.imageOverlay = L.imageOverlay(imageUrl, bounds).addTo(this.map);
            this.map.fitBounds(bounds);

            // Draw initial grid
            this.redrawGrid();
        };

        img.onerror = () => {
            console.error('Failed to load image:', imageUrl);
            // Show placeholder on error
            if (this.hasPlaceholderTarget) {
                this.placeholderTarget.classList.remove('d-none');
            }
            if (this.hasPreviewTarget) {
                this.previewTarget.classList.add('d-none');
            }
        };
    }

    /**
     * Redraw grid overlay with current settings
     */
    redrawGrid() {
        if (!this.map || !this.imageWidth || !this.imageHeight) {
            return;
        }

        // Remove existing grid layer
        if (this.gridLayer) {
            this.map.removeLayer(this.gridLayer);
            this.gridLayer = null;
        }

        // Don't draw if grid is not visible
        if (!this.gridVisibleValue) {
            return;
        }

        const rows = this.gridRowsValue;
        const cols = this.gridColumnsValue;
        const opacity = this.gridOpacityValue;
        const width = this.imageWidth;
        const height = this.imageHeight;

        const cellWidth = width / cols;
        const cellHeight = height / rows;

        this.gridLayer = L.layerGroup().addTo(this.map);

        // Draw horizontal lines
        for (let i = 0; i <= rows; i++) {
            const y = i * cellHeight;
            L.polyline([[y, 0], [y, width]], {
                color: '#000000',
                weight: 1,
                opacity: opacity
            }).addTo(this.gridLayer);
        }

        // Draw vertical lines
        for (let i = 0; i <= cols; i++) {
            const x = i * cellWidth;
            L.polyline([[0, x], [height, x]], {
                color: '#000000',
                weight: 1,
                opacity: opacity
            }).addTo(this.gridLayer);
        }

        // Add grid labels (only if grid is not too dense)
        if (rows <= 26 && cols <= 26) {
            for (let row = 0; row < rows; row++) {
                for (let col = 0; col < cols; col++) {
                    const x = col * cellWidth + cellWidth / 2;
                    const y = row * cellHeight + cellHeight / 2;
                    const label = this.getCellLabel(row, col);

                    // Only show labels if cells are large enough
                    if (cellWidth > 30 && cellHeight > 30) {
                        L.marker([y, x], {
                            icon: L.divIcon({
                                className: 'map-grid-label',
                                html: `<div class="grid-label-text" style="opacity: ${opacity};">${label}</div>`,
                                iconSize: [30, 30],
                                iconAnchor: [15, 15]
                            })
                        }).addTo(this.gridLayer);
                    }
                }
            }
        }
    }

    /**
     * Get cell label (A1, B2, etc.)
     */
    getCellLabel(row, col) {
        const letter = String.fromCharCode(65 + col); // A, B, C, ...
        return `${letter}${row + 1}`;
    }

    /**
     * Zoom to fit the entire image
     */
    fitImage() {
        if (this.map && this.imageWidth && this.imageHeight) {
            const bounds = [[0, 0], [this.imageHeight, this.imageWidth]];
            this.map.fitBounds(bounds);
        }
    }
}
