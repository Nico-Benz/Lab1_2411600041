
const LOW_GRADE_THRESHOLD = 75;   // equivalent of "reorder level" for low stock
const STORAGE_KEY = 'portalActivities';
const API_URL = 'api/student.php';

let activities = [];

async function initializeData() {
    try {
        const response = await fetch(API_URL, { cache: 'no-store' });
        if (!response.ok) throw new Error('API responded with an error');
        const data = await response.json();
        if (Array.isArray(data) && data.length > 0) {
            activities = data;
            saveLocal();
            return activities;
        }
        throw new Error('API returned no data');
    } catch (err) {
        console.warn('Could not load from API, using local fallback:', err.message);
        return initializeFromLocalFallback();
    }
}

function initializeFromLocalFallback() {
    const saved = localStorage.getItem(STORAGE_KEY);
    if (saved) {
        activities = JSON.parse(saved);
        return activities;
    }
    
    saveLocal();
    return activities;
}

function saveLocal() {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(activities));
}

// ---- Basic getters ----
function getActivities() {
    return activities;
}

function getActivityById(id) {
    return activities.find(a => a.id === id) || null;
}

function getActivitiesBySemester(semester) {
    if (semester === 'all') return activities;
    return activities.filter(a => String(a.semester) === String(semester));
}

function getActivitiesByStatus(status) {
    if (status === 'all' || status === '') return activities;
    return activities.filter(a => a.status === status);
}

function getLowGradeActivities() {
    return activities.filter(a => a.grade < LOW_GRADE_THRESHOLD);
}

// ---- Statistics ----
function getGradeStatistics() {
    const total = activities.length;
    const avgGrade = total ? activities.reduce((sum, a) => sum + a.grade, 0) / total : 0;
    const lowGradeCount = getLowGradeActivities().length;
    const semesters = [...new Set(activities.map(a => a.semester))];
    return {
        totalRecords: total,
        averageGrade: parseFloat(avgGrade.toFixed(2)),
        lowGradeCount,
        semesterCount: semesters.length
    };
}

function getSemesterSummary() {
    const groups = {};
    activities.forEach(a => {
        if (!groups[a.semester]) groups[a.semester] = [];
        groups[a.semester].push(a.grade);
    });
    return Object.keys(groups).map(sem => {
        const grades = groups[sem];
        const avg = grades.reduce((a, b) => a + b, 0) / grades.length;
        return {
            semester: sem,
            averageGrade: parseFloat(avg.toFixed(2)),
            gpa: parseFloat(((avg / 100) * 4).toFixed(2)),
            count: grades.length
        };
    });
}

function getStatusDistribution() {
    const counts = { success: 0, warning: 0, danger: 0, info: 0 };
    activities.forEach(a => {
        if (counts[a.status] !== undefined) counts[a.status]++;
    });
    return counts;
}

// ---- Filtering ----
function filterBySemester(list, semester) {
    if (semester === 'all') return list;
    return list.filter(a => String(a.semester) === String(semester));
}

function filterByStatus(list, status) {
    if (status === 'all' || status === '') return list;
    return list.filter(a => a.status === status);
}

function filterByGradeRange(list, min, max) {
    const lo = (min === null || min === '' || isNaN(min)) ? -Infinity : Number(min);
    const hi = (max === null || max === '' || isNaN(max)) ? Infinity : Number(max);
    return list.filter(a => a.grade >= lo && a.grade <= hi);
}

function applyFilters(state) {
    let result = getActivities();
    result = filterBySemester(result, state.semester);
    result = filterByStatus(result, state.status);
    result = filterByGradeRange(result, state.minGrade, state.maxGrade);
    if (state.search) {
        result = searchActivities(result, state.search);
    }
    return result;
}

// ---- Search ----
function searchActivities(list, query) {
    const q = query.trim().toLowerCase();
    if (!q) return list;
    return list.filter(a => a.activity.toLowerCase().includes(q));
}

// ---- CSV Export ----
function exportToCSV(data) {
    let csvContent = 'Date,Activity,Status,Grade,Semester\n';
    data.forEach(a => {
        csvContent += `${a.date},"${a.activity}",${a.status},${a.grade},${a.semester}\n`;
    });
    return csvContent;
}

function downloadCSV(csvContent, filename) {
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8,' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.setAttribute('href', url);
    link.setAttribute('download', filename);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
}

// ---- Real-time simulation ----
/**
 * simulateUpdate()
 * Nudges one activity's grade and pushes the change to the PHP API
 * (Part 7, Step 3: "Implement Write Operations"), falling back to
 * local-only if the API isn't reachable.
 */
async function simulateUpdate() {
    if (activities.length === 0) return null;
    const index = Math.floor(Math.random() * activities.length);
    const target = activities[index];

    const change = Math.floor(Math.random() * 7) - 3; // -3 to +3
    target.grade = Math.max(0, Math.min(100, target.grade + change));

    if (target.grade >= 90) target.status = 'success';
    else if (target.grade >= 80) target.status = 'info';
    else if (target.grade >= LOW_GRADE_THRESHOLD) target.status = 'warning';
    else target.status = 'danger';

    saveLocal();

    try {
        await fetch(API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'update', id: target.id, grade: target.grade, status: target.status })
        });
    } catch (err) {
        console.warn('Could not sync update to API (offline?):', err.message);
    }

    return target;
}

window.DataManager = {
    initializeData,
    getActivities,
    getActivityById,
    getActivitiesBySemester,
    getActivitiesByStatus,
    getLowGradeActivities,
    getGradeStatistics,
    getSemesterSummary,
    getStatusDistribution,
    filterBySemester,
    filterByStatus,
    filterByGradeRange,
    applyFilters,
    searchActivities,
    exportToCSV,
    downloadCSV,
    simulateUpdate,
    LOW_GRADE_THRESHOLD
};