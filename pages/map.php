<?php
session_start();
$base_path = '../';
require_once $base_path . 'config/db.php';
require_once $base_path . 'includes/lang.php';

require_once $base_path . 'includes/header.php';
require_once $base_path . 'includes/navbar.php';

$current_lang_code = $_SESSION['lang'];
?>

<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

<style>
    #map {
        height: 75vh;
        width: 100%;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        z-index: 1;
    }
    
    .map-container {
        position: relative;
    }

    /* Custom Leaflet Popup Styling - Moroccan Theme */
    .leaflet-popup-content-wrapper {
        border-radius: 12px;
        padding: 0;
        overflow: hidden;
        border-top: 4px solid #198754; /* Moroccan Green */
    }
    
    .leaflet-popup-content {
        margin: 0;
        width: 280px !important;
        font-family: inherit;
    }

    .popup-header {
        background-color: #f8f9fa;
        padding: 12px 15px;
        border-bottom: 1px solid #eee;
    }

    .popup-header h5 {
        margin: 0;
        color: #dc3545; /* Moroccan Red */
        font-weight: bold;
    }

    .popup-body {
        padding: 15px;
    }

    .popup-img {
        width: 100%;
        height: 120px;
        object-fit: cover;
        border-radius: 8px;
        margin-bottom: 10px;
    }

    .leaflet-container a.leaflet-popup-close-button {
        color: #333;
        padding: 8px;
    }
    
    /* Marker Icons */
    .custom-marker {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background-color: #198754;
        color: white;
        box-shadow: 0 3px 6px rgba(0,0,0,0.3);
        border: 2px solid white;
        font-size: 16px;
    }
    
    .marker-headquarters { background-color: #dc3545; }
    .marker-etat_civil { background-color: #0d6efd; }
    .marker-school { background-color: #fd7e14; }
    .marker-health { background-color: #20c997; }
    .marker-mosque { background-color: #198754; }
    
    /* Layer Control specific styling for RTL */
    <?php if($current_lang_code == 'ar'): ?>
    .leaflet-control-layers-list { text-align: right; }
    .leaflet-popup-content { direction: rtl; text-align: right; }
    .leaflet-container a.leaflet-popup-close-button { right: auto; left: 0; }
    <?php endif; ?>
</style>

<div class="container-fluid py-4 bg-light">
    <div class="container mb-4">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h2 class="fw-bold text-primary mb-2">
                    <i class="fa-solid fa-map-location-dot me-2"></i><?php echo __('map_title'); ?>
                </h2>
                <p class="text-muted mb-0"><?php echo __('map_subtitle'); ?></p>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <button class="btn btn-outline-success shadow-sm rounded-pill" onclick="locateUser()">
                    <i class="fa-solid fa-location-crosshairs me-2"></i><?php echo __('my_location'); ?>
                </button>
            </div>
        </div>
    </div>

    <div class="container-fluid px-md-5">
        <div class="map-container">
            <div id="map"></div>
        </div>
    </div>
</div>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
    // Map Configuration
    // Beni Chegdale coordinates
    const centerLat = 32.44425;
    const centerLng = -6.96037;
    const defaultZoom = 15;
    const currentLang = '<?php echo $current_lang_code; ?>';

    // Translations for JS
    const translations = {
        directions: '<?php echo __('get_directions'); ?>',
        visit_service: '<?php echo __('visit_service'); ?>',
        contact: '<?php echo __('contact'); ?>',
        working_hours: '<?php echo __('working_hours'); ?>'
    };

    // Initialize Map
    const map = L.map('map').setView([centerLat, centerLng], defaultZoom);

    // Define Tile Layers (Standard, Satellite, Dark)
    const osmLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    });

    const satelliteLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        attribution: 'Tiles &copy; Esri'
    });

    const darkLayer = L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; CARTO'
    });

    // Add default layer
    osmLayer.addTo(map);

    // Add Layer Control
    const baseMaps = {
        "Standard": osmLayer,
        "Satellite": satelliteLayer,
        "Sombre / Dark": darkLayer
    };
    L.control.layers(baseMaps).addTo(map);

    // FontAwesome icon mapper based on type
    function getIconClass(type) {
        switch(type) {
            case 'headquarters': return 'fa-solid fa-building-columns';
            case 'etat_civil': return 'fa-solid fa-file-signature';
            case 'legalisation': return 'fa-solid fa-stamp';
            case 'tax': return 'fa-solid fa-coins';
            case 'social': return 'fa-solid fa-hands-holding-child';
            case 'school': return 'fa-solid fa-school';
            case 'health': return 'fa-solid fa-hospital';
            case 'mosque': return 'fa-solid fa-mosque';
            default: return 'fa-solid fa-location-dot';
        }
    }

    // Fetch and display locations
    fetch('<?php echo $base_path; ?>api/get_locations.php')
        .then(response => response.json())
        .then(res => {
            if(res.status === 'success') {
                const locations = res.data;
                locations.forEach(loc => {
                    // Create custom DivIcon
                    const iconClass = getIconClass(loc.type);
                    const markerClass = `custom-marker marker-${loc.type}`;
                    
                    const customIcon = L.divIcon({
                        className: 'custom-icon',
                        html: `<div class="${markerClass}"><i class="${iconClass}"></i></div>`,
                        iconSize: [36, 36],
                        iconAnchor: [18, 36],
                        popupAnchor: [0, -36]
                    });

                    // Prepare localized text
                    const name = currentLang === 'ar' ? loc.name_ar : loc.name_fr;
                    const desc = currentLang === 'ar' ? loc.description_ar : loc.description_fr;
                    const hours = currentLang === 'ar' ? loc.working_hours_ar : loc.working_hours_fr;
                    
                    // Build Popup Content
                    let popupHtml = `
                        <div class="popup-header">
                            <h5>${name}</h5>
                        </div>
                        <div class="popup-body">
                    `;

                    if(loc.image) {
                        popupHtml += `<img src="<?php echo $base_path; ?>uploads/locations/${loc.image}" class="popup-img" alt="${name}">`;
                    }

                    if(desc) popupHtml += `<p class="mb-2 text-muted small">${desc}</p>`;
                    
                    if(hours) popupHtml += `<p class="mb-1 small"><i class="fa-solid fa-clock text-warning me-1"></i> <strong>${translations.working_hours}:</strong><br>${hours}</p>`;
                    
                    if(loc.contact_info) popupHtml += `<p class="mb-3 small"><i class="fa-solid fa-phone text-success me-1"></i> <strong>${translations.contact}:</strong> ${loc.contact_info}</p>`;
                    
                    // Buttons
                    popupHtml += `<div class="d-grid gap-2">
                        <a href="https://www.google.com/maps/dir/?api=1&destination=${loc.latitude},${loc.longitude}" target="_blank" class="btn btn-sm btn-primary">
                            <i class="fa-solid fa-directions me-1"></i>${translations.directions}
                        </a>
                    `;

                    if(loc.service_link && loc.service_link.trim() !== '') {
                        popupHtml += `<a href="${loc.service_link}" class="btn btn-sm btn-outline-success">
                            <i class="fa-solid fa-arrow-right me-1"></i>${translations.visit_service}
                        </a>`;
                    }

                    popupHtml += `</div></div>`; // Close body and buttons

                    // Add Marker to Map
                    L.marker([loc.latitude, loc.longitude], {icon: customIcon})
                        .addTo(map)
                        .bindPopup(popupHtml);
                });
            }
        });

    // User Geolocation Function
    let userMarker = null;
    function locateUser() {
        if (!navigator.geolocation) {
            alert("La géolocalisation n'est pas supportée par votre navigateur.");
        } else {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    
                    if(userMarker) { map.removeLayer(userMarker); }

                    const userIcon = L.divIcon({
                        className: 'custom-icon',
                        html: `<div class="custom-marker" style="background-color: #0d6efd;"><i class="fa-solid fa-user"></i></div>`,
                        iconSize: [36, 36],
                        iconAnchor: [18, 36]
                    });

                    userMarker = L.marker([lat, lng], {icon: userIcon}).addTo(map);
                    map.setView([lat, lng], 14);
                },
                () => {
                    alert("Impossible de récupérer votre position.");
                }
            );
        }
    }
</script>

<?php require_once $base_path . 'includes/footer.php'; ?>
