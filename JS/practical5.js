
const form = document.getElementById("registrationForm");

form.addEventListener("submit", function (event) {
    event.preventDefault();

    // Read input values
    const name = document.getElementById("name").value.trim();
    const email = document.getElementById("email").value.trim();
    const mobile = document.getElementById("mobile").value.trim();
    const password = document.getElementById("password").value;
    const confirmPassword = document.getElementById("confirmPassword").value;
    const course = document.getElementById("course").value;
    const year = document.getElementById("year").value;
    const gender = document.querySelector('input[name="gender"]:checked');
    const terms = document.getElementById("terms").checked;

    // Regular expressions
    const namePattern = /^[A-Za-z]+(?:[ '-][A-Za-z]+)*$/;
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    const mobilePattern = /^[6-9]\d{9}$/;
    const passwordPattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;

    // Clear previous errors
    document.querySelectorAll("small").forEach(function (element) {
        element.textContent = "";
    });

    document.getElementById("successMessage").textContent = "";

    let valid = true;

    // Name validation
    if (!namePattern.test(name)) {
        document.getElementById("nameError").textContent =
            "Enter a valid name using letters and spaces.";
        valid = false;
    }

    // Email validation
    if (!emailPattern.test(email)) {
        document.getElementById("emailError").textContent =
            "Enter a valid email address.";
        valid = false;
    }

    // Mobile validation
    if (!mobilePattern.test(mobile)) {
        document.getElementById("mobileError").textContent =
            "Enter a valid 10-digit Indian mobile number.";
        valid = false;
    }

    // Password validation
    if (!passwordPattern.test(password)) {
        document.getElementById("passwordError").textContent =
            "Use 8+ characters with uppercase, lowercase, number and special character.";
        valid = false;
    }

    // Confirm password validation
    if (password !== confirmPassword || confirmPassword === "") {
        document.getElementById("confirmPasswordError").textContent =
            "Passwords do not match.";
        valid = false;
    }

    // Course validation
    if (course === "") {
        document.getElementById("courseError").textContent =
            "Please select a course.";
        valid = false;
    }

    // Year validation
    if (year === "") {
        document.getElementById("yearError").textContent =
            "Please select your year.";
        valid = false;
    }

    // Gender validation
    if (!gender) {
        document.getElementById("genderError").textContent =
            "Please select your gender.";
        valid = false;
    }

    // Terms validation
    if (!terms) {
        document.getElementById("termsError").textContent =
            "You must accept the terms and conditions.";
        valid = false;
    }

    // Final result
    if (valid) {
        document.getElementById("successMessage").textContent =
            "Registration successful!";

        form.reset();
    }
});
