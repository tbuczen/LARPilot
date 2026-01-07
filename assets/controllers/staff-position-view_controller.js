import { Controller } from '@hotwired/stimulus';
import L from 'leaflet';
import 'leaflet/dist/leaflet.min.css';

/**
 * Stimulus controller for viewing staff positions on a game map.
 * Displays markers for all visible staff members with their position info.
 */
export default class extends Controller {
    static values = {
        imageUrl: String,
        gridRows: Number,
        gridColumns: Number,
        gridOpacity: Number,
        positions: Array,
        apiUrl: String
    };

    connect() {
        this.positionsLayer = null;
        this.initMap();
    }

    disconnect() {
        if (this.map) {
            this.map.remove();
        }
    }

    initMap() {
        const img = new Image();
        img.src = this.imageUrlValue;

        img.onload = () => {
            this.imageWidth = img.width;
            this.imageHeight = img.height;
            const bounds = [[0, 0], [img.height, img.width]];

            this.map = L.map('mapContainer', {
                crs: L.CRS.Simple,
                minZoom: -2,
                maxZoom: 2,
                center: [img.height / 2, img.width / 2],
                zoom: 0,
                zoomControl: true,
                attributionControl: false
            });

            // Add the image overlay
            L.imageOverlay(this.imageUrlValue, bounds).addTo(this.map);
            this.map.fitBounds(bounds);

            // Draw grid
            this.drawGrid();

            // Add position markers
            this.renderPositions(this.positionsValue);
        };
    }

    drawGrid() {
        const rows = this.gridRowsValue;
        const cols = this.gridColumnsValue;
        const cellWidth = this.imageWidth / cols;
        const cellHeight = this.imageHeight / rows;

        this.gridLayer = L.layerGroup().addTo(this.map);

        // Draw horizontal lines
        for (let i = 0; i <= rows; i++) {
            const y = i * cellHeight;
            L.polyline([[y, 0], [y, this.imageWidth]], {
                color: 'black',
                weight: 1,
                opacity: this.gridOpacityValue
            }).addTo(this.gridLayer);
        }

        // Draw vertical lines
        for (let i = 0; i <= cols; i++) {
            const x = i * cellWidth;
            L.polyline([[0, x], [this.imageHeight, x]], {
                color: 'black',
                weight: 1,
                opacity: this.gridOpacityValue
            }).addTo(this.gridLayer);
        }

        // Add grid labels
        for (let row = 0; row < rows; row++) {
            for (let col = 0; col < cols; col++) {
                const x = col * cellWidth + cellWidth / 2;
                const y = row * cellHeight + cellHeight / 2;
                const label = this.getCellLabel(row, col);

                L.marker([y, x], {
                    icon: L.divIcon({
                        className: 'grid-label',
                        html: `<div style="font-size: 10px; color: rgba(0,0,0,${this.gridOpacityValue}); font-weight: bold; pointer-events: none;">${label}</div>`,
                        iconSize: [25, 15]
                    }),
                    interactive: false
                }).addTo(this.gridLayer);
            }
        }
    }

    renderPositions(positions) {
        // Remove previous positions layer
        if (this.positionsLayer) {
            this.positionsLayer.remove();
        }

        this.positionsLayer = L.layerGroup().addTo(this.map);

        if (!positions || positions.length === 0) {
            return;
        }

        positions.forEach(position => {
            this.addPositionMarker(position);
        });
    }

    addPositionMarker(position) {
        const { row, col } = this.parseCellLabel(position.gridCell);
        const cellWidth = this.imageWidth / this.gridColumnsValue;
        const cellHeight = this.imageHeight / this.gridRowsValue;

        const centerX = col * cellWidth + cellWidth / 2;
        const centerY = row * cellHeight + cellHeight / 2;

        // Determine marker color based on role
        const color = this.getRoleColor(position.roles);

        // Create marker
        const marker = L.marker([centerY, centerX], {
            icon: L.divIcon({
                className: 'staff-position-marker',
                html: `
                    <div style="
                        background-color: ${color};
                        width: 28px;
                        height: 28px;
                        border-radius: 50%;
                        border: 2px solid white;
                        box-shadow: 0 0 6px rgba(0,0,0,0.5);
                        display: flex;
                        align-items: center;
                        justify-content: center;
                    ">
                        <i class="bi bi-person-fill" style="color: white; font-size: 14px;"></i>
                    </div>
                `,
                iconSize: [28, 28],
                iconAnchor: [14, 14]
            })
        }).addTo(this.positionsLayer);

        // Create popup content
        let popupContent = `
            <div style="min-width: 150px;">
                <strong>${position.participantName}</strong><br>
                <small class="text-muted">
                    ${position.roles.map(r => r.replace('ROLE_', '').toLowerCase()).join(', ')}
                </small><br>
                <strong>${position.gridCell}</strong>
                ${position.statusNote ? `<br><em>${position.statusNote}</em>` : ''}
                <br><small class="text-muted">Updated: ${position.updatedAt}</small>
            </div>
        `;

        marker.bindPopup(popupContent);
    }

    getRoleColor(roles) {
        // Color coding by role priority
        if (roles.includes('ROLE_ORGANIZER')) {
            return '#dc3545'; // Red - main organizer
        }
        if (roles.includes('ROLE_PERSON_OF_TRUST')) {
            return '#28a745'; // Green - trust person
        }
        if (roles.includes('ROLE_PHOTOGRAPHER')) {
            return '#17a2b8'; // Cyan - photographer
        }
        if (roles.includes('ROLE_MEDIC')) {
            return '#ffc107'; // Yellow - medic
        }
        if (roles.includes('ROLE_GAME_MASTER')) {
            return '#6f42c1'; // Purple - game master
        }
        if (roles.includes('ROLE_STAFF')) {
            return '#fd7e14'; // Orange - staff
        }
        return '#6c757d'; // Gray - other
    }

    getCellLabel(row, col) {
        const letter = String.fromCharCode(65 + col);
        return `${letter}${row + 1}`;
    }

    parseCellLabel(label) {
        const match = label.match(/^([A-Z]+)(\d+)$/);
        if (!match) {
            return { row: 0, col: 0 };
        }

        const col = match[1].charCodeAt(0) - 65;
        const row = parseInt(match[2]) - 1;
        return { row, col };
    }

    async refresh() {
        if (!this.apiUrlValue) {
            return;
        }

        try {
            const response = await fetch(this.apiUrlValue);
            if (!response.ok) {
                throw new Error('Failed to fetch positions');
            }

            const data = await response.json();
            this.renderPositions(data.positions);
        } catch (error) {
            console.error('Error refreshing positions:', error);
        }
    }
}
