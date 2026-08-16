// dashboard.js — handles auth guard, greeting, stats, and activity table

// ----- Auth Guard -----
// If the user is not logged in, redirect them back to the login page
if (localStorage.getItem("isLoggedIn") !== "true") {
  window.location.href = "index.html";
}

// ----- Display logged-in username -----
const username = localStorage.getItem("username") || "Student";
document.getElementById("navUserName").textContent = "Welcome, " + username;

// ----- Time-based Greeting -----
function updateGreeting() {
  const hour = new Date().getHours();
  let greeting;

  if (hour < 12) {
    greeting = "Good morning";
  } else if (hour < 18) {
    greeting = "Good afternoon";
  } else {
    greeting = "Good evening";
  }

  document.getElementById("greeting-text").textContent =
    greeting + ", " + username + "!";
}

// ----- Statistic Cards (Student Grade Portal theme) -----
function updateStatistics() {
  const stats = [
    { title: "GPA", value: "1.75" },
    { title: "Courses", value: "6" },
    { title: "Assignments Due", value: "4" },
    { title: "Attendance", value: "96%" }
  ];

  stats.forEach(function (stat, index) {
    const num = index + 1;
    document.getElementById("stat" + num + "-title").textContent = stat.title;
    document.getElementById("stat" + num + "-value").textContent = stat.value;
  });
}

// ----- Recent Activity Table -----
function populateActivityTable() {
  const activities = [
    { date: "Aug 12, 2026", course: "Web Development", activity: "Lab Exercise 2 Submitted", status: "Complete" },
    { date: "Aug 13, 2026", course: "Data Structures", activity: "Quiz 3", status: "Pending" },
    { date: "Aug 10, 2026", course: "Database Systems", activity: "Project Proposal", status: "Complete" },
    { date: "Aug 08, 2026", course: "Networking", activity: "Assignment 4", status: "Missed" }
  ];

  const tableBody = document.getElementById("activity-table-body");
  tableBody.innerHTML = ""; // clear any existing rows

  activities.forEach(function (item) {
    let badgeClass = "status-pending";
    if (item.status === "Complete") badgeClass = "status-complete";
    if (item.status === "Missed") badgeClass = "status-missed";

    const row = document.createElement("tr");
    row.innerHTML =
      "<td>" + item.date + "</td>" +
      "<td>" + item.course + "</td>" +
      "<td>" + item.activity + "</td>" +
      "<td><span class='status-badge " + badgeClass + "'>" + item.status + "</span></td>";

    tableBody.appendChild(row);
  });
}

// ----- Logout -----
document.getElementById("logoutBtn").addEventListener("click", function () {
  localStorage.removeItem("isLoggedIn");
  localStorage.removeItem("username");
  window.location.href = "index.html";
});

// ----- Run everything on page load -----
updateGreeting();
updateStatistics();
populateActivityTable();