document.addEventListener("DOMContentLoaded", () => {
    const forms = document.querySelectorAll("form");

    forms.forEach(form => {
        form.addEventListener("submit", async (e) => {
            e.preventDefault(); 
            const formData = new FormData(form);
            let targetEndpoint = "";

            // Route the form submission to the correct PHP file based on Form ID
            switch (form.id) {
                case "register-form":
                case "login-form":
                    targetEndpoint = "../PHP/auth.php";
                    formData.append("action", form.id === "register-form" ? "register" : "login");
                    break;
                case "post-skill-form":
                    targetEndpoint = "../PHP/post_skill.php";
                    break;
                case "booking-form":
                    targetEndpoint = "../PHP/book_session.php";
                    break;
                case "review-form":
                    targetEndpoint = "../PHP/submit_review.php";
                    break;
                case "verification-form":
                    targetEndpoint = "../PHP/verify_profile.php";
                    break;
                default:
                    alert("This form is not connected to the backend yet.");
                    return;
            }

            try {
                const response = await fetch(targetEndpoint, {
                    method: "POST",
                    body: formData
                });
                
                const data = await response.json();
                alert(data.message);
                
                if (data.status === "success") {
                    form.reset();
                }
            } catch (error) {
                console.error("Server error:", error);
                alert("An error occurred while connecting to the server.");
            }
        });
    });

    // --- Dynamic Data Fetching Logic ---

// Fetch and display skills on the Search page
if (document.getElementById("skills-results-grid")) {
    fetch("../PHP/fetch_skills.php")
        .then(response => response.json())
        .then(data => {
            if (data.status === "success") {
                const grid = document.getElementById("skills-results-grid");
                grid.innerHTML = ""; // Clear placeholders
                
                data.data.forEach(skill => {
                    const priceDisplay = skill.mode === "Barter" ? "Barter Exchange" : `$${skill.price}`;
                    grid.innerHTML += `
                        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                            <h3 style="color: #1e3a8a; margin-bottom: 10px;">${skill.title}</h3>
                            <p><strong>Mentor:</strong> ${skill.mentor_name}</p>
                            <p><strong>Category:</strong> ${skill.category}</p>
                            <p><strong>Mode:</strong> <span style="color: #2563eb; font-weight: bold;">${priceDisplay}</span></p>
                            <a href="session-booking.html?id=${skill.skill_id}" style="display: inline-block; margin-top: 15px; padding: 8px 15px; background: #2563eb; color: white; text-decoration: none; border-radius: 5px;">Book Now</a>
                        </div>
                    `;
                });
            }
        });
}

// Fetch and display sessions on the Calendar page
if (document.getElementById("calendar-session-list")) {
    fetch("../PHP/fetch_sessions.php")
        .then(response => response.json())
        .then(data => {
            if (data.status === "success") {
                const list = document.getElementById("calendar-session-list");
                list.innerHTML = ""; // Clear placeholders
                
                data.data.forEach(session => {
                    list.innerHTML += `
                        <li>
                            <div>
                                <strong>${session.title}</strong><br>
                                <span style="color: #555;">${session.booking_date} at ${session.booking_time}</span>
                            </div>
                            <a href="online-meeting.html">Join Room</a>
                        </li>
                    `;
                });
            }
        });
}
});