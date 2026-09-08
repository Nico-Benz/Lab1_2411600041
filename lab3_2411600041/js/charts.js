  ============================================================ */

let chartInstances = {
    performance: null,
    attendance: null,
    gpaTrend: null,
    statusDistribution: null
};

function renderPerformanceChart() {
    const gpa = parseFloat(document.getElementById('stat1-value')?.textContent) || 0;
    const stats = DataManager.getGradeStatistics();
    const attendance = parseFloat(document.getElementById('stat4-value')?.textContent) || 0;

    const ctx = document.getElementById('performanceChart')?.getContext('2d');
    if (!ctx) return;
    if (chartInstances.performance) chartInstances.performance.destroy();

    chartInstances.performance = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['GPA', 'Records', 'Avg Grade', 'Attendance'],
            datasets: [{
                label: 'Student Stats',
                data: [gpa, stats.totalRecords, stats.averageGrade, attendance],
                backgroundColor: ['#007bff', '#28a745', '#17a2b8', '#ffc107']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                title: { display: true, text: 'Student Performance Overview' }
            }
        }
    });
}

function renderAttendanceChart() {
    const attendanceText = document.getElementById('stat4-value')?.textContent || '0%';
    const attendanceValue = parseFloat(attendanceText.replace('%', '')) || 0;
    const present = attendanceValue;
    const absent = 100 - attendanceValue;

    const ctx = document.getElementById('attendanceChart')?.getContext('2d');
    if (!ctx) return;
    if (chartInstances.attendance) chartInstances.attendance.destroy();

    chartInstances.attendance = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: ['Present', 'Absent'],
            datasets: [{
                data: [present, absent],
                backgroundColor: ['#28a745', '#dc3545']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { title: { display: true, text: 'Attendance Distribution' } }
        }
    });
}

function renderGpaTrendChart() {
    const summary = DataManager.getSemesterSummary();
    const labels = summary.map(s => `Semester ${s.semester}`);
    const gpaHistory = summary.map(s => s.gpa);

    const ctx = document.getElementById('gpaTrendChart')?.getContext('2d');
    if (!ctx) return;
    if (chartInstances.gpaTrend) chartInstances.gpaTrend.destroy();

    chartInstances.gpaTrend = new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: 'GPA',
                data: gpaHistory,
                borderColor: '#007bff',
                backgroundColor: 'rgba(0, 123, 255, 0.2)',
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { title: { display: true, text: 'GPA Trend Over Semesters' } },
            scales: { y: { beginAtZero: true, max: 4 } }
        }
    });
}

// The lab's "at least one additional chart" requirement
function renderStatusDistributionChart() {
    const dist = DataManager.getStatusDistribution();
    const ctx = document.getElementById('statusDistributionChart')?.getContext('2d');
    if (!ctx) return;
    if (chartInstances.statusDistribution) chartInstances.statusDistribution.destroy();

    chartInstances.statusDistribution = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Success', 'Warning', 'Danger', 'Info'],
            datasets: [{
                data: [dist.success, dist.warning, dist.danger, dist.info],
                backgroundColor: ['#28a745', '#ffc107', '#dc3545', '#17a2b8']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { title: { display: true, text: 'Record Status Distribution' } }
        }
    });
}

function renderAllCharts() {
    renderPerformanceChart();
    renderAttendanceChart();
    renderGpaTrendChart();
    renderStatusDistributionChart();
}

window.Charts = {
    renderAllCharts,
    renderPerformanceChart,
    renderAttendanceChart,
    renderGpaTrendChart,
    renderStatusDistributionChart
};