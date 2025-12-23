import { Controller } from '@hotwired/stimulus';

/**
 * Map Marker Editor Controller
 *
 * Allows placing a single marker on a map image using percentage-based coordinates.
 * The marker can be dragged to reposition.
 */
export default class extends Controller {
    static values = {
        imageUrl: String,
        positionX: { type: Number, default: 50 },
        positionY: { type: Number, default: 50 },
        shape: { type: String, default: 'dot' },
        color: { type: String, default: '#3388ff' }
    };

    static targets = ['container', 'positionX', 'positionY', 'shape', 'color', 'displayX', 'displayY'];

    connect() {
        this.initMap();
    }

    disconnect() {
        if (this.resizeObserver) {
            this.resizeObserver.disconnect();
        }
    }

    initMap() {
        const container = this.containerTarget;

        // Create image element
        this.imageElement = document.createElement('img');
        this.imageElement.src = this.imageUrlValue;
        this.imageElement.style.cssText = 'max-width: 100%; max-height: 100%; object-fit: contain; display: block; margin: auto;';

        // Create wrapper for image and marker
        this.wrapper = document.createElement('div');
        this.wrapper.style.cssText = 'position: relative; display: inline-block; max-width: 100%; max-height: 100%;';
        this.wrapper.appendChild(this.imageElement);

        container.innerHTML = '';
        container.style.display = 'flex';
        container.style.alignItems = 'center';
        container.style.justifyContent = 'center';
        container.appendChild(this.wrapper);

        this.imageElement.onload = () => {
            this.createMarker();
            this.updateMarkerPosition();
            this.setupClickHandler();
        };

        // Handle resize
        this.resizeObserver = new ResizeObserver(() => {
            this.updateMarkerPosition();
        });
        this.resizeObserver.observe(container);
    }

    createMarker() {
        this.marker = document.createElement('div');
        this.marker.className = 'map-marker';
        this.marker.style.cssText = `
            position: absolute;
            width: 24px;
            height: 24px;
            transform: translate(-50%, -50%);
            cursor: grab;
            z-index: 100;
            pointer-events: auto;
        `;

        this.updateMarkerAppearance();
        this.wrapper.appendChild(this.marker);

        // Setup drag handling
        this.setupDragHandler();
    }

    updateMarkerAppearance() {
        if (!this.marker) return;

        const shape = this.hasShapeTarget ? this.shapeTarget.value : this.shapeValue;
        const color = this.hasColorTarget ? this.colorTarget.value : this.colorValue;

        // Create SVG based on shape
        const svgContent = this.getSvgForShape(shape, color);
        this.marker.innerHTML = svgContent;
    }

    getSvgForShape(shape, color) {
        const shapes = {
            dot: `<circle cx="12" cy="12" r="8" fill="${color}" stroke="white" stroke-width="2"/>`,
            circle: `<circle cx="12" cy="12" r="10" fill="none" stroke="${color}" stroke-width="3"/>`,
            square: `<rect x="2" y="2" width="20" height="20" fill="${color}" stroke="white" stroke-width="2"/>`,
            diamond: `<polygon points="12,2 22,12 12,22 2,12" fill="${color}" stroke="white" stroke-width="2"/>`,
            triangle: `<polygon points="12,2 22,22 2,22" fill="${color}" stroke="white" stroke-width="2"/>`,
            house: `<path d="M12,2L2,10v12h8v-6h4v6h8V10z" fill="${color}" stroke="white" stroke-width="2"/>`,
            arrow_up: `<path d="M12,2L22,14H16v8H8v-8H2z" fill="${color}" stroke="white" stroke-width="2"/>`,
            arrow_down: `<path d="M12,22L2,10H8V2h8v8h6z" fill="${color}" stroke="white" stroke-width="2"/>`,
            arrow_left: `<path d="M2,12L14,2v6h8v8h-8v6z" fill="${color}" stroke="white" stroke-width="2"/>`,
            arrow_right: `<path d="M22,12L10,22v-6H2V8h8V2z" fill="${color}" stroke="white" stroke-width="2"/>`,
            star: `<polygon points="12,2 15,8.5 22,9.5 17,15 18.2,22 12,18 5.8,22 7,15 2,9.5 9,8.5" fill="${color}" stroke="white" stroke-width="2"/>`,
            flag: `<path d="M4,2v20h2v-8h12l-4-6l4-6z" fill="${color}" stroke="white" stroke-width="2"/>`,
            pin: `<path d="M12,2C8,2 5,5 5,9c0,5 7,13 7,13s7-8 7-13c0-4-3-7-7-7z" fill="${color}" stroke="white" stroke-width="2"/>`,
            cross: `<path d="M4,8h6V2h4v6h6v4h-6v6h-4v-6H4z" fill="${color}" stroke="white" stroke-width="2"/>`
        };

        const shapeSvg = shapes[shape] || shapes.dot;

        return `<svg width="24" height="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">${shapeSvg}</svg>`;
    }

    updateMarkerPosition() {
        if (!this.marker || !this.imageElement) return;

        const imgWidth = this.imageElement.offsetWidth;
        const imgHeight = this.imageElement.offsetHeight;

        const x = (this.positionXValue / 100) * imgWidth;
        const y = (this.positionYValue / 100) * imgHeight;

        this.marker.style.left = `${x}px`;
        this.marker.style.top = `${y}px`;

        this.updateDisplays();
    }

    updateDisplays() {
        if (this.hasDisplayXTarget) {
            this.displayXTarget.textContent = this.positionXValue.toFixed(2);
        }
        if (this.hasDisplayYTarget) {
            this.displayYTarget.textContent = this.positionYValue.toFixed(2);
        }
    }

    updateFormFields() {
        if (this.hasPositionXTarget) {
            this.positionXTarget.value = this.positionXValue.toFixed(4);
        }
        if (this.hasPositionYTarget) {
            this.positionYTarget.value = this.positionYValue.toFixed(4);
        }
    }

    setupClickHandler() {
        this.imageElement.style.cursor = 'crosshair';
        this.imageElement.addEventListener('click', (e) => {
            const rect = this.imageElement.getBoundingClientRect();
            const x = ((e.clientX - rect.left) / rect.width) * 100;
            const y = ((e.clientY - rect.top) / rect.height) * 100;

            this.positionXValue = Math.max(0, Math.min(100, x));
            this.positionYValue = Math.max(0, Math.min(100, y));

            this.updateMarkerPosition();
            this.updateFormFields();
        });
    }

    setupDragHandler() {
        let isDragging = false;

        this.marker.addEventListener('mousedown', (e) => {
            isDragging = true;
            this.marker.style.cursor = 'grabbing';
            e.preventDefault();
        });

        document.addEventListener('mousemove', (e) => {
            if (!isDragging) return;

            const rect = this.imageElement.getBoundingClientRect();
            const x = ((e.clientX - rect.left) / rect.width) * 100;
            const y = ((e.clientY - rect.top) / rect.height) * 100;

            this.positionXValue = Math.max(0, Math.min(100, x));
            this.positionYValue = Math.max(0, Math.min(100, y));

            this.updateMarkerPosition();
            this.updateFormFields();
        });

        document.addEventListener('mouseup', () => {
            if (isDragging) {
                isDragging = false;
                this.marker.style.cursor = 'grab';
            }
        });
    }

    // Actions
    onShapeChange() {
        this.updateMarkerAppearance();
    }

    onColorChange() {
        this.updateMarkerAppearance();
    }

    centerMarker() {
        this.positionXValue = 50;
        this.positionYValue = 50;
        this.updateMarkerPosition();
        this.updateFormFields();
    }

    fitImage() {
        // Reset zoom/pan if we add that feature later
        // For now, just center the marker
        this.updateMarkerPosition();
    }
}
