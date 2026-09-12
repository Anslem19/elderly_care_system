<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>System Functionalities</title>
    <link rel="stylesheet" href="/elderly_care/assets/css/style.css">
</head>
<body class="scrollable-page">

    <div class="actor-container">
        <h2>System Functionalities Matrix</h2>
        <p style="color: #7f8c8d; margin-bottom: 30px;">
            A complete architectural breakdown of the features implemented within the Online Elderly Care Management System, mapped by user permissions.
        </p>

        <div class="func-grid">
            
            <!-- COLUMN 1: ADMINISTRATOR FEATURES -->
            <div class="panel-card">
                <span class="actor-badge badge-admin">System Administrator</span>
                <h3>Core Operations & Management</h3>
                <p style="font-size: 13px; color: #7f8c8d;">Main governing authority responsible for configuration and oversight.</p>
                
                <ul class="func-list">
                    <li><strong>Secure Authentication:</strong> System login and role clearance restrictions.</li>
                    <li><strong>Caregiver Directory Management:</strong> Full CRUD operations to register and manage active care staff.</li>
                    <li><strong>Elderly Resident Registration:</strong> Data entry for patient records with explicit staff assignment rules.</li>
                    <li><strong>Medical Appointment Planner:</strong> Scheduling clinical checkups and doctor allocations.</li>
                    <li><strong>Daily Task Allocation:</strong> Designing treatment plans and routine schedules for caregivers.</li>
                    <li><strong>Family Request Arbitration:</strong> Reviewing, approving, or declining incoming visit notifications.</li>
                </ul>
            </div>

            <!-- COLUMN 2: CAREGIVER FEATURES -->
            <div class="panel-card">
                <span class="actor-badge badge-caregiver">Caregiver / Nurse</span>
                <h3>Clinical & Daily Care Tracking</h3>
                <p style="font-size: 13px; color: #7f8c8d;">Frontline staff acting on assigned treatment schedules and logs.</p>
                
                <ul class="func-list">
                    <li><strong>Personal Dashboard:</strong> Visualizing localized shift structures.</li>
                    <li><strong>Task Schedule Execution:</strong> Viewing active profiles and real-time instructions allocated by the Admin.</li>
                    <li><strong>Medication Status Logging:</strong> One-click updates to shift timelines to change status from pending to completed.</li>
                    <li><strong>Treatment History Maintenance:</strong> Tracking today's continuous care delivery entries.</li>
                </ul>
            </div>

            <!-- COLUMN 3: FAMILY MEMBER FEATURES -->
            <div class="panel-card">
                <span class="actor-badge badge-family">Family / Relative</span>
                <h3>Remote Monitoring Portal</h3>
                <p style="font-size: 13px; color: #7f8c8d;">External transparency link ensuring accessibility and peace of mind.</p>
                
                <ul class="func-list">
                    <li><strong>Patient Vitals Overview:</strong> Reviewing current stable/critical conditions and daily clinical notes.</li>
                    <li><strong>Appointment Visibility:</strong> Monitoring upcoming scheduled clinic dates and specialized doctor visits.</li>
                    <li><strong>Visit Request Submissions:</strong> Interactive forms to push special care notes or visit dates into the backend database queue.</li>
                </ul>
            </div>

        </div>
    </div>

    <script src="/elderly_care/assets/js/navbar.js"></script>
</body>
</html>