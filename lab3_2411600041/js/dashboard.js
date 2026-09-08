
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

    const userNameSpan = document.getElementById('userName');
    if (userNameSpan) {
        userNameSpan.textContent = username;
    }

    setupLogout();

    await DataManager.initializeData(); // Part 7: tries the PHP API first, falls back to localStorage/sample data

    populateSemesterFilterOptions();
    setupFilterListeners();
    setupSortListeners();
    setupExport();
    setupRealTimeSimulation();

    refreshDashboard();
});


// Greeting

function updateGreeting(username) {
    const greetingElement = document.getElementById('greeting');
    if (!greetingElement) return;

    const hour = new Date().getHours();
    let timeOfDay = '';

    if (hour >= 5 && hour < 12) {
        timeOfDay = 'Good Morning';
    } else if (hour >= 12 && hour < 17) {
        timeOfDay = 'Good Afternoon';
    } else if (hour >= 17 && hour < 21) {
        timeOfDay = 'Good Evening';
    } else {
        timeOfDay = 'Good Night';
    }

    greetingElement.textContent = `${timeOfDay}, ${username}!`;
}


// Stat cards — now driven by DataManager instead of hardcoded values

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
        const titleElement = document.getElementById(`stat${index + 1}-title`);
        const valueElement = document.getElementById(`stat${index + 1}-value`);

        if (titleElement) {
            titleElement.textContent = `${card.icon} ${card.title}`;
        }
        if (valueElement) {
            valueElement.textContent = card.value;
            valueElement.className = `card-text fw-bold ${card.color}`;
        }
    });
}


// Filters & Search (Lab 4)

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


// Table: filtering + sorting + rendering

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

function populateActivityTable() {
    renderTable();
}

function renderTable() {
    const tableBody = document.getElementById('activityTableBody');
    if (!tableBody) return;

    const filtered = getFilteredSortedData();

    tableBody.innerHTML = '';

    if (filtered.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">No matching records.</td></tr>';
        return;
    }

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


// Alerts (Lab 4)

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
// CSV Export (Lab 4)
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
// Real-time simulation + toast (Lab 4)
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
// Logout — same IDs as Lab 3 (top navbar button + sidebar link)
// ---------------------------------------------------------------
function setupLogout() {
    const logoutBtn = document.getElementById('logoutBtn');
    const logoutLink = document.getElementById('logoutLink');

    function performLogout(e) {
        e.preventDefault();
        localStorage.removeItem('isLoggedIn');
        localStorage.removeItem('user');
        window.location.href = 'index.html';
    }

    if (logoutBtn) {
        logoutBtn.addEventListener('click', performLogout);
    }
    if (logoutLink) {
        logoutLink.addEventListener('click', performLogout);
    }
}

// ---------------------------------------------------------------
// Master refresh
// ---------------------------------------------------------------
function refreshDashboard() {
    updateStatistics();
    renderTable();
    showAlerts();
    Charts.renderAllCharts();
}
