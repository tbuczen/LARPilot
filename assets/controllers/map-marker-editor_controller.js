import { Controller } from '@hotwired/stimulus';
import L from 'leaflet';
import 'leaflet/dist/leaflet.min.css';

/**
 * Map Marker Editor Controller (Leaflet version)
 *
 * Allows placing a single marker on a map image using percentage-based coordinates.
 * Uses Leaflet for professional map interactions with zoom/pan.
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
        if (this.map) {
            this.map.remove();
        }
    }

    initMap() {
        const container = this.containerTarget;

        // Create map div
        this.mapDiv = document.createElement('div');
        this.mapDiv.style.cssText = 'width: 100%; height: 100%;';
        container.innerHTML = '';
        container.appendChild(this.mapDiv);

        // Load image to get dimensions
        const img = new Image();
        img.src = this.imageUrlValue;

        img.onload = () => {
            this.imageWidth = img.width;
            this.imageHeight = img.height;
            const bounds = [[0, 0], [img.height, img.width]];

            // Initialize Leaflet map
            this.map = L.map(this.mapDiv, {
                crs: L.CRS.Simple,
                minZoom: -3,
                maxZoom: 3,
                center: [img.height / 2, img.width / 2],
                zoom: 0
            });

            // Add image overlay
            L.imageOverlay(this.imageUrlValue, bounds).addTo(this.map);
            this.map.fitBounds(bounds);

            // Create the marker
            this.createMarker();

            // Setup click handler on map
            this.map.on('click', (e) => this.onMapClick(e));
        };
    }

    createMarker() {
        const shape = this.hasShapeTarget ? this.shapeTarget.value : this.shapeValue;
        const color = this.hasColorTarget ? this.colorTarget.value : this.colorValue;

        // Convert percentage to pixel coordinates
        const x = (this.positionXValue / 100) * this.imageWidth;
        const y = (this.positionYValue / 100) * this.imageHeight;

        // Create custom icon
        const icon = this.createMarkerIcon(shape, color);

        // Create draggable marker
        this.marker = L.marker([y, x], {
            icon: icon,
            draggable: true
        }).addTo(this.map);

        // Handle drag events
        this.marker.on('drag', (e) => this.onMarkerDrag(e));
        this.marker.on('dragend', (e) => this.onMarkerDrag(e));

        this.updateDisplays();
    }

    createMarkerIcon(shape, color) {
        const svgContent = this.getSvgForShape(shape, color);

        return L.divIcon({
            className: 'map-marker-editor-icon',
            html: `<div style="
                width: 32px;
                height: 32px;
                display: flex;
                align-items: center;
                justify-content: center;
                filter: drop-shadow(0 2px 4px rgba(0,0,0,0.5));
            ">${svgContent}</div>`,
            iconSize: [32, 32],
            iconAnchor: [16, 16]
        });
    }

    getSvgForShape(shape, color) {
        const shapes = {
            dot: `<circle cx="12" cy="12" r="8" fill="${color}" stroke="white" stroke-width="2"/>`,
            circle: `<circle cx="12" cy="12" r="10" fill="${color}" fill-opacity="0.3" stroke="${color}" stroke-width="3"/>`,
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
            pin: `<path d="M12,2C8,2 5,5 5,9c0,5 7,13 7,13s7-8 7-13c0-4-3-7-7-7z" fill="${color}" stroke="white" stroke-width="2"/><circle cx="12" cy="9" r="3" fill="white"/>`,
            cross: `<path d="M4,8h6V2h4v6h6v4h-6v6h-4v-6H4z" fill="${color}" stroke="white" stroke-width="2"/>`
        };

        const shapeSvg = shapes[shape] || shapes.dot;

        return `<svg width="24" height="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">${shapeSvg}</svg>`;
    }

    updateMarkerAppearance() {
        if (!this.marker) return;

        const shape = this.hasShapeTarget ? this.shapeTarget.value : this.shapeValue;
        const color = this.hasColorTarget ? this.colorTarget.value : this.colorValue;

        const icon = this.createMarkerIcon(shape, color);
        this.marker.setIcon(icon);
    }

    onMapClick(e) {
        // Convert pixel coordinates to percentage
        const x = (e.latlng.lng / this.imageWidth) * 100;
        const y = (e.latlng.lat / this.imageHeight) * 100;

        this.positionXValue = Math.max(0, Math.min(100, x));
        this.positionYValue = Math.max(0, Math.min(100, y));

        // Move marker to new position
        const newLat = (this.positionYValue / 100) * this.imageHeight;
        const newLng = (this.positionXValue / 100) * this.imageWidth;
        this.marker.setLatLng([newLat, newLng]);

        this.updateFormFields();
        this.updateDisplays();
    }

    onMarkerDrag(e) {
        const latlng = e.target.getLatLng();

        // Convert pixel coordinates to percentage
        const x = (latlng.lng / this.imageWidth) * 100;
        const y = (latlng.lat / this.imageHeight) * 100;

        this.positionXValue = Math.max(0, Math.min(100, x));
        this.positionYValue = Math.max(0, Math.min(100, y));

        this.updateFormFields();
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

    // Actions triggered by form field changes
    onShapeChange() {
        this.updateMarkerAppearance();
    }

    onColorChange() {
        this.updateMarkerAppearance();
    }

    centerMarker() {
        this.positionXValue = 50;
        this.positionYValue = 50;

        const newLat = (this.positionYValue / 100) * this.imageHeight;
        const newLng = (this.positionXValue / 100) * this.imageWidth;
        this.marker.setLatLng([newLat, newLng]);

        this.updateFormFields();
        this.updateDisplays();
    }

    fitImage() {
        if (this.map && this.imageWidth && this.imageHeight) {
            const bounds = [[0, 0], [this.imageHeight, this.imageWidth]];
            this.map.fitBounds(bounds);
        }
    }
}
