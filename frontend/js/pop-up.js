$(document).ready(function() {
    // Parse URL parameters to get user's first name, last name, and name prefix
    const urlParams = new URLSearchParams(window.location.search);
    const firstName = urlParams.get('first_name');
    const lastName = urlParams.get('last_name');
    const namePrefix = urlParams.get('name_prefix');

    console.log("First Name:", firstName);
    console.log("Last Name:", lastName);
    console.log("Name Prefix:", namePrefix);

    // Check if the name details are available
    if (firstName && lastName && namePrefix) {
        // Display the welcome message in the modal
        $('#welcomeMessage').text(`Welcome ${namePrefix} ${firstName} ${lastName}! Please fill in your assessment kit.`);
        $('#welcomeModal').modal('show');
    }

    // Handle the close button on the modal
    $('#closeBtn').on('click', function() {
        $('#welcomeModal').modal('hide');
    });
});
