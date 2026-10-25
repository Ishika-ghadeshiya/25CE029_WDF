
const studentList = document.getElementById("studentList");
const searchInput = document.getElementById("search");
const courseFilter = document.getElementById("courseFilter");
const sortBy = document.getElementById("sortBy");
const pagination = document.getElementById("pagination");
const resultCount = document.getElementById("resultCount");
const message = document.getElementById("message");

let students = [];
let currentPage = 1;
const studentsPerPage = 6;

// Fetch student data from external JSON file
async function loadStudents() {
    try {
        const response = await fetch("data/students.json");

        if (!response.ok) {
            throw new Error("Unable to load student data.");
        }

        students = await response.json();

        message.textContent = "";
        displayStudents();
    } catch (error) {
        message.textContent =
            "Failed to load students. Run the project using Live Server.";
        resultCount.textContent = "";
        console.error(error);
    }
}

// Search, filter and sort the data
function getFilteredStudents() {
    const searchText = searchInput.value.trim().toLowerCase();
    const selectedCourse = courseFilter.value;
    const selectedSort = sortBy.value;

    let filtered = students.filter(function (student) {
        const matchesSearch =
            student.name.toLowerCase().includes(searchText) ||
            student.email.toLowerCase().includes(searchText);

        const matchesCourse =
            selectedCourse === "all" ||
            student.course === selectedCourse;

        return matchesSearch && matchesCourse;
    });

    // Sorting
    filtered.sort(function (a, b) {
        if (selectedSort === "nameAsc") {
            return a.name.localeCompare(b.name);
        }

        if (selectedSort === "nameDesc") {
            return b.name.localeCompare(a.name);
        }

        if (selectedSort === "yearAsc") {
            return a.year - b.year;
        }

        if (selectedSort === "yearDesc") {
            return b.year - a.year;
        }
    });

    return filtered;
}

// Render student cards and pagination
function displayStudents() {
    const filteredStudents = getFilteredStudents();
    const totalPages = Math.ceil(
        filteredStudents.length / studentsPerPage
    );

    // Keep the current page valid after filtering
    if (totalPages > 0 && currentPage > totalPages) {
        currentPage = totalPages;
    }

    if (totalPages === 0) {
        currentPage = 1;
    }

    const startIndex = (currentPage - 1) * studentsPerPage;
    const pageStudents = filteredStudents.slice(
        startIndex,
        startIndex + studentsPerPage
    );

    studentList.replaceChildren();
    pagination.replaceChildren();

    resultCount.textContent =
        `Showing ${filteredStudents.length} student(s)`;

    if (filteredStudents.length === 0) {
        message.textContent = "No students found.";
        return;
    }

    message.textContent = "";

    // Create cards dynamically
    pageStudents.forEach(function (student) {
        const card = document.createElement("article");
        card.className = "student-card";

        const name = document.createElement("h2");
        name.textContent = student.name;

        const email = document.createElement("p");
        email.textContent = `Email: ${student.email}`;

        const course = document.createElement("span");
        course.className = "course";
        course.textContent = student.course;

        const year = document.createElement("p");
        year.textContent = `Year: ${student.year}`;

        card.append(name, email, course, year);
        studentList.appendChild(card);
    });

    // Previous button
    const previousButton = document.createElement("button");
    previousButton.textContent = "Previous";
    previousButton.disabled = currentPage === 1;

    previousButton.addEventListener("click", function () {
        if (currentPage > 1) {
            currentPage--;
            displayStudents();
        }
    });

    pagination.appendChild(previousButton);

    // Page number buttons
    for (let page = 1; page <= totalPages; page++) {
        const button = document.createElement("button");
        button.textContent = page;

        if (page === currentPage) {
            button.className = "active";
            button.setAttribute("aria-current", "page");
        }

        button.addEventListener("click", function () {
            currentPage = page;
            displayStudents();
        });

        pagination.appendChild(button);
    }

    // Next button
    const nextButton = document.createElement("button");
    nextButton.textContent = "Next";
    nextButton.disabled = currentPage === totalPages;

    nextButton.addEventListener("click", function () {
        if (currentPage < totalPages) {
            currentPage++;
            displayStudents();
        }
    });

    pagination.appendChild(nextButton);
}

// Reset to page 1 whenever controls change
searchInput.addEventListener("input", function () {
    currentPage = 1;
    displayStudents();
});

courseFilter.addEventListener("change", function () {
    currentPage = 1;
    displayStudents();
});

sortBy.addEventListener("change", function () {
    currentPage = 1;
    displayStudents();
});

// Start the application
loadStudents();
