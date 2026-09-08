/* ============================================================
   dashboard.js
   Enhanced Student Grade Portal Dashboard
   Depends on: dataManager.js, charts.js (both loaded before this file)
   ============================================================ */

const state = {
    search: '',
    semester: 'all',
    status: 'all',
    minGrade: '',
    maxGrade: '',
    sortColumn: null,
    sortDir: 'asc'
};

document.addEventListener('DOMContentLoaded', async function () {
    const isLoggedIn = localStorage.getItem('isLoggedIn');
    if (isLoggedIn !== 'true') {
        window.location.href = 'index.html';
        return;
    }

    const username = localStorage.getItem('user') || 'User';
    updateGreeting(username);

    const userNameSpan = document.getElementById('userNameNav');
    if (userNameSpan) userNameSpan.textContent = username;

    await DataManager.initializeData(); // now async: tries the PHP API first

    populateSemesterFilterOptions();
    setupLogout();
    setupFilterListeners();
    setupSortListeners();
    setupExport();
    setupRealTimeSimulation();

    refreshDashboard();
});

// ---------------------------------------------------------------
// Greeting & top stat cards
// ---------------------------------------------------------------
function updateGreeting(username) {
    const greetingElement = document.getElementById('greeting');
    if (!greetingElement) return;

    const hour = new Date().getHours();
    let timeOfDay = '';
    if (hour >= 5 && hour < 12) timeOfDay = 'Good Morning';
    else if (hour >= 12 && hour < 17) timeOfDay = 'Good Afternoon';
    else if (hour >= 17 && hour < 21) timeOfDay = 'Good Evening';
    else timeOfDay = 'Good Night';

    greetingElement.textContent = `${timeOfDay}, ${username}!`;
}

function updateStatistics() {
    const stats = DataManager.getGradeStatistics();
    const semesterSummary = DataManager.getSemesterSummary();
    const overallGpa = semesterSummary.length
        ? parseFloat((semesterSummary.reduce((s, x) => s + x.gpa, 0) / semesterSummary.length).toFixed(2))
        : 0;

    const cards = [
        { title: 'GPA', value: overallGpa.toFixed(2), color: 'text-primary', icon: '🎓' },
        { title: 'Records', value: stats.totalRecords, color: 'text-success', icon: '📚' },
        { title: 'Avg Grade', value: stats.averageGrade, color: 'text-info', icon: '📝' },
        { title: 'Attendance', value: '96%', color: 'text-warning', icon: '📅' }
    ];

    cards.forEach((card, index) => {
        const titleEl = document.getElementById(`stat${index + 1}-title`);
        const valueEl = document.getElementById(`stat${index + 1}-value`);
        if (titleEl) titleEl.textContent = `${card.icon} ${card.title}`;
        if (valueEl) {
            valueEl.textContent = card.value;
            valueEl.className = `card-text fw-bold ${card.color}`;
        }
    });

    const gpaElement = document.getElementById('stat1-value');
    if (gpaElement) {
        gpaElement.className = 'card-text fw-bold';
        if (overallGpa >= 3.0) gpaElement.classList.add('text-success');
        else if (overallGpa >= 2.0) gpaElement.classList.add('text-warning');
        else gpaElement.classList.add('text-danger');
    }
}

// ---------------------------------------------------------------
// Filters, Search
// ---------------------------------------------------------------
function populateSemesterFilterOptions() {
    const select = document.getElementById('semesterFilter');
    if (!select) return;
    const semesters = [...new Set(DataManager.getActivities().map(a => a.semester))];
    select.innerHTML = '<option value="all">All Semesters</option>' +
        semesters.map(s => `<option value="${s}">Semester ${s}</option>`).join('');
}

function setupFilterListeners() {
    const searchBox = document.getElementById('searchBox');
    const semesterFilter = document.getElementById('semesterFilter');
    const statusFilter = document.getElementById('statusFilter');

    if (searchBox) {
        searchBox.addEventListener('keyup', function () {
            state.search = this.value;
            refreshDashboard();
        });
    }

    if (semesterFilter) {
        semesterFilter.addEventListener('change', function () {
            state.semester = this.value;
            refreshDashboard();
        });
    }

    if (statusFilter) {
        statusFilter.addEventListener('change', function () {
            state.status = this.value;
            refreshDashboard();
        });
    }

    const applyGradeRangeBtn = document.getElementById('applyGradeRange');
    if (applyGradeRangeBtn) {
        applyGradeRangeBtn.addEventListener('click', function () {
            state.minGrade = document.getElementById('minGrade').value;
            state.maxGrade = document.getElementById('maxGrade').value;
            refreshDashboard();
        });
    }

    const clearFiltersBtn = document.getElementById('clearFilters');
    if (clearFiltersBtn) {
        clearFiltersBtn.addEventListener('click', function () {
            state.search = '';
            state.semester = 'all';
            state.status = 'all';
            state.minGrade = '';
            state.maxGrade = '';
            if (searchBox) searchBox.value = '';
            if (semesterFilter) semesterFilter.value = 'all';
            if (statusFilter) statusFilter.value = 'all';
            document.getElementById('minGrade').value = '';
            document.getElementById('maxGrade').value = '';
            refreshDashboard();
        });
    }
}

// ---------------------------------------------------------------
// Table rendering
// ---------------------------------------------------------------
function getFilteredSortedData() {
    let data = DataManager.applyFilters(state);

    if (state.sortColumn !== null) {
        data = [...data].sort((a, b) => {
            const cols = ['date', 'activity', 'status', 'grade'];
            const key = cols[state.sortColumn];
            let aVal = a[key];
            let bVal = b[key];
            if (key === 'date') {
                aVal = new Date(aVal);
                bVal = new Date(bVal);
            } else if (typeof aVal === 'string') {
                aVal = aVal.toLowerCase();
                bVal = bVal.toLowerCase();
            }
            if (aVal < bVal) return state.sortDir === 'asc' ? -1 : 1;
            if (aVal > bVal) return state.sortDir === 'asc' ? 1 : -1;
            return 0;
        });
    }

    return data;
}

function renderTable() {
    const tableBody = document.getElementById('activityTableBody');
    if (!tableBody) return;

    const filtered = getFilteredSortedData();

    tableBody.innerHTML = '';

    if (filtered.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">No matching records.</td></tr>';
    } else {
        filtered.forEach(activity => {
            const row = document.createElement('tr');
            let badgeClass = 'bg-secondary';
            if (activity.status === 'success') badgeClass = 'bg-success';
            else if (activity.status === 'warning') badgeClass = 'bg-warning text-dark';
            else if (activity.status === 'danger') badgeClass = 'bg-danger';
            else if (activity.status === 'info') badgeClass = 'bg-info text-dark';

            if (activity.grade < DataManager.LOW_GRADE_THRESHOLD) {
                row.classList.add('low-grade-row');
            }

            row.innerHTML = `
                <td>${activity.date}</td>
                <td>${activity.activity}</td>
                <td><span class="badge ${badgeClass}">${activity.status}</span></td>
                <td>${activity.grade}</td>
            `;
            tableBody.appendChild(row);
        });
    }
}

function setupSortListeners() {
    const map = { sortDate: 0, sortActivity: 1, sortStatus: 2 };
    Object.keys(map).forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('click', () => {
            const columnIndex = map[id];
            if (state.sortColumn === columnIndex) {
                state.sortDir = state.sortDir === 'asc' ? 'desc' : 'asc';
            } else {
                state.sortColumn = columnIndex;
                state.sortDir = 'asc';
            }
            renderTable();
        });
    });
}

// ---------------------------------------------------------------
// Alerts
// ---------------------------------------------------------------
function showAlerts() {
    const alertSection = document.getElementById('alertSection');
    if (!alertSection) return;
    alertSection.innerHTML = '';

    const stats = DataManager.getGradeStatistics();
    const attendanceText = document.getElementById('stat4-value')?.textContent || '100%';
    const attendance = parseFloat(attendanceText.replace('%', '')) || 100;

    if (stats.averageGrade < 75) {
        alertSection.innerHTML += `
            <div class="alert alert-danger" role="alert">
                ⚠ Average grade is below 75 — needs improvement!
            </div>`;
    }

    if (attendance < 75) {
        alertSection.innerHTML += `
            <div class="alert alert-warning" role="alert">
                ⚠ Attendance is below 75% — please attend more classes!
            </div>`;
    }

    if (stats.lowGradeCount > 0) {
        alertSection.innerHTML += `
            <div class="alert alert-warning" role="alert">
                ⚠ ${stats.lowGradeCount} record(s) below ${DataManager.LOW_GRADE_THRESHOLD} — check the highlighted rows in the table.
            </div>`;
    }
}

// ---------------------------------------------------------------
// Export
// ---------------------------------------------------------------
function setupExport() {
    const exportBtn = document.getElementById('exportBtn');
    if (!exportBtn) return;
    exportBtn.addEventListener('click', function () {
        const data = getFilteredSortedData();
        const csvContent = DataManager.exportToCSV(data);
        DataManager.downloadCSV(csvContent, 'activity_table.csv');
    });
}

// ---------------------------------------------------------------
// Real-time simulation
// ---------------------------------------------------------------
function setupRealTimeSimulation() {
    setInterval(async () => {
        const updated = await DataManager.simulateUpdate();
        if (!updated) return;
        refreshDashboard();
        showToast(`Updated: "${updated.activity}" → grade now ${updated.grade}`);
    }, 15000);
}

function showToast(message) {
    const toastContainer = document.getElementById('toastContainer');
    if (!toastContainer) return;
    const toast = document.createElement('div');
    toast.className = 'app-toast';
    toast.textContent = message;
    toastContainer.appendChild(toast);
    setTimeout(() => toast.remove(), 4000);
}

// ---------------------------------------------------------------
// Logout
// ---------------------------------------------------------------
function setupLogout() {
    const logoutBtn = document.getElementById('logoutBtnNav');
    const logoutLink = document.getElementById('logoutLink');

    function performLogout(e) {
        e.preventDefault();
        localStorage.removeItem('isLoggedIn');
        localStorage.removeItem('user');
        window.location.href = 'index.html';
    }

    if (logoutBtn) logoutBtn.addEventListener('click', performLogout);
    if (logoutLink) logoutLink.addEventListener('click', performLogout);
}

// ---------------------------------------------------------------
// Recent Activity — a simple, unfiltered feed of the latest records
// (separate from the big filterable Activity Table below it)
// ---------------------------------------------------------------
function renderRecentActivity() {
    const container = document.getElementById('recentActivityList');
    if (!container) return;

    const recent = [...DataManager.getActivities()]
        .sort((a, b) => new Date(b.date) - new Date(a.date))
        .slice(0, 5);

    if (recent.length === 0) {
        container.innerHTML = '<li class="list-group-item text-muted">No recent activity.</li>';
        return;
    }

    container.innerHTML = recent.map(a => {
        let cls = 'bg-secondary';
        if (a.status === 'success') cls = 'bg-success';
        else if (a.status === 'warning') cls = 'bg-warning text-dark';
        else if (a.status === 'danger') cls = 'bg-danger';
        else if (a.status === 'info') cls = 'bg-info text-dark';

        return `
            <li class="list-group-item d-flex justify-content-between align-items-center">
                <div>
                    <div class="fw-semibold">${a.activity}</div>
                    <small class="text-muted">${a.date}</small>
                </div>
                <span class="badge ${cls}">${a.status}</span>
            </li>`;
    }).join('');
}

// ---------------------------------------------------------------
// Master refresh
// ---------------------------------------------------------------
function refreshDashboard() {
    updateStatistics();
    renderRecentActivity();
    renderTable();
    showAlerts();
    Charts.renderAllCharts();
}