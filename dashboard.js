// Dashboard JavaScript - Functionality and API calls

const API_BASE = 'api/';
let currentUser = null;

// Initialize dashboard
document.addEventListener('DOMContentLoaded', function() {
    loadUserInfo();
    loadDashboardData();
    setupEventListeners();
});

function setupEventListeners() {
    // Profile form
    const profileForm = document.getElementById('profileForm');
    if (profileForm) {
        profileForm.addEventListener('submit', (e) => {
            e.preventDefault();
            updateProfile();
        });
    }
}

function loadUserInfo() {
    fetch(API_BASE + 'get-user-info.php')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                currentUser = data.user;
                document.getElementById('userName').textContent = data.user.name;
                document.getElementById('userRole').textContent = data.user.role;
                
                // Show admin menu for admins
                if (data.user.role === 'admin') {
                    document.querySelectorAll('.admin-only').forEach(el => {
                        el.style.display = 'block';
                    });
                }
                
                loadProfileData();
            }
        })
        .catch(err => console.error('Error loading user info:', err));
}

function loadProfileData() {
    if (!currentUser) return;
    
    document.getElementById('profileName').value = currentUser.name;
    document.getElementById('profileEmail').value = currentUser.email;
    document.getElementById('profilePhone').value = currentUser.phone || '';
}

function loadDashboardData() {
    fetch(API_BASE + 'get-dashboard-stats.php')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                displayDashboardCards(data.stats);
            }
        })
        .catch(err => console.error('Error loading dashboard data:', err));
}

function displayDashboardCards(stats) {
    const container = document.getElementById('dashboardCards');
    container.innerHTML = '';
    
    const cards = [
        { title: 'Total Appointments', value: stats.totalAppointments || 0, icon: '📅' },
        { title: 'Pending Appointments', value: stats.pendingAppointments || 0, icon: '⏳' },
        { title: 'Completed Appointments', value: stats.completedAppointments || 0, icon: '✓' },
        { title: 'Available Doctors', value: stats.availableDoctors || 0, icon: '👨‍⚕️' }
    ];
    
    cards.forEach(card => {
        const cardEl = document.createElement('div');
        cardEl.className = 'card';
        cardEl.innerHTML = `
            <h3>${card.icon} ${card.title}</h3>
            <div class="card-value">${card.value}</div>
        `;
        container.appendChild(cardEl);
    });
}

function showSection(sectionId, event) {
    if (event) event.preventDefault();
    
    // Hide all sections
    document.querySelectorAll('.content-section').forEach(section => {
        section.classList.remove('active');
    });
    
    // Update active nav link
    document.querySelectorAll('.nav-link').forEach(link => {
        link.classList.remove('active');
    });
    
    // Show selected section
    const section = document.getElementById(sectionId);
    if (section) {
        section.classList.add('active');
        event.target.classList.add('active');
        
        // Load section specific data
        if (sectionId === 'appointments') {
            loadAppointments();
        } else if (sectionId === 'doctors') {
            loadDoctors();
        }
    }
}

function loadAppointments() {
    fetch(API_BASE + 'get-appointments.php')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                displayAppointments(data.appointments);
            } else {
                showMessage('appointmentMessage', data.message, 'error');
            }
        })
        .catch(err => {
            showMessage('appointmentMessage', 'Error loading appointments', 'error');
            console.error(err);
        });
}

function displayAppointments(appointments) {
    const container = document.getElementById('appointmentsList');
    
    if (appointments.length === 0) {
        container.innerHTML = '<p>No appointments found.</p>';
        return;
    }
    
    let html = '<table class="table"><thead><tr>';
    html += '<th>Doctor</th><th>Date</th><th>Time</th><th>Clinic</th><th>Status</th><th>Action</th>';
    html += '</tr></thead><tbody>';
    
    appointments.forEach(apt => {
        const statusClass = 'status-' + apt.status;
        html += `<tr>
            <td>${apt.doctor_name}</td>
            <td>${apt.appointment_date}</td>
            <td>${apt.appointment_time}</td>
            <td>${apt.clinic_name || 'N/A'}</td>
            <td><span class="status-badge ${statusClass}">${apt.status}</span></td>
            <td>
                ${apt.status === 'pending' ? `<button class="btn-danger" onclick="cancelAppointment(${apt.appointment_id})">Cancel</button>` : ''}
            </td>
        </tr>`;
    });
    
    html += '</tbody></table>';
    container.innerHTML = html;
}

function cancelAppointment(appointmentId) {
    if (!confirm('Are you sure you want to cancel this appointment?')) return;
    
    fetch(API_BASE + 'cancel-appointment.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'appointment_id=' + appointmentId
    })
    .then(res => res.json())
    .then(data => {
        showMessage('appointmentMessage', data.message, data.success ? 'success' : 'error');
        if (data.success) {
            loadAppointments();
        }
    })
    .catch(err => console.error('Error:', err));
}

function loadDoctors() {
    fetch(API_BASE + 'get-doctors.php')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                displayDoctors(data.doctors);
            }
        })
        .catch(err => console.error('Error loading doctors:', err));
}

function displayDoctors(doctors) {
    const container = document.getElementById('doctorsList');
    
    if (doctors.length === 0) {
        container.innerHTML = '<p>No doctors available.</p>';
        return;
    }
    
    let html = '<div class="cards-grid">';
    doctors.forEach(doctor => {
        html += `
            <div class="card">
                <h3>${doctor.full_name}</h3>
                <p><strong>Specialization:</strong> ${doctor.specialization}</p>
                <p><strong>Experience:</strong> ${doctor.experience_years} years</p>
                <p><strong>License:</strong> ${doctor.license_number}</p>
                <p>${doctor.bio || 'No bio available'}</p>
                <button class="btn-primary" onclick="bookAppointment(${doctor.doctor_id}, '${doctor.full_name}')">Book Appointment</button>
            </div>
        `;
    });
    html += '</div>';
    container.innerHTML = html;
}

function bookAppointment(doctorId, doctorName) {
    const appointmentDate = prompt('Enter appointment date (YYYY-MM-DD):');
    if (!appointmentDate) return;
    
    const appointmentTime = prompt('Enter appointment time (HH:MM):');
    if (!appointmentTime) return;
    
    const reason = prompt('Enter reason for appointment:');
    
    const formData = new FormData();
    formData.append('doctor_id', doctorId);
    formData.append('appointment_date', appointmentDate);
    formData.append('appointment_time', appointmentTime);
    formData.append('reason', reason || '');
    
    fetch(API_BASE + 'book-appointment.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        showMessage('appointmentMessage', data.message, data.success ? 'success' : 'error');
        if (data.success) {
            loadAppointments();
        }
    })
    .catch(err => {
        showMessage('appointmentMessage', 'Error booking appointment', 'error');
        console.error(err);
    });
}

function updateProfile() {
    const formData = new FormData(document.getElementById('profileForm'));
    
    fetch(API_BASE + 'update-profile.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        showMessage('profileMessage', data.message, data.success ? 'success' : 'error');
        if (data.success) {
            loadUserInfo();
        }
    })
    .catch(err => {
        showMessage('profileMessage', 'Error updating profile', 'error');
        console.error(err);
    });
}

function showMessage(elementId, message, type) {
    const element = document.getElementById(elementId);
    if (element) {
        element.textContent = message;
        element.className = `message show ${type}`;
        setTimeout(() => {
            element.classList.remove('show');
        }, 5000);
    }
}

function showAppointmentForm() {
    alert('Use the Doctors section to book an appointment');
}

function showAdminSection(section) {
    fetch(API_BASE + 'admin/get-' + section + '.php')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                displayAdminContent(section, data);
            }
        })
        .catch(err => console.error('Error:', err));
}

function logout(event) {
    if (event) event.preventDefault();
    
    fetch(API_BASE + 'logout.php', { method: 'POST' })
        .then(() => {
            window.location.href = 'index.html';
        })
        .catch(err => console.error('Error:', err));
}