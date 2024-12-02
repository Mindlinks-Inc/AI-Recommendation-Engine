$(document).ready(function() {
    let verificationSent = false;
    let verificationConfirmed = false;
    let timerInterval;

    // Timer function
    function startTimer(duration) {
        let timer = duration, minutes, seconds;
        clearInterval(timerInterval); // Clear any existing timer

        timerInterval = setInterval(function () {
            minutes = parseInt(timer / 60, 10);
            seconds = parseInt(timer % 60, 10);

            minutes = minutes < 10 ? "0" + minutes : minutes;
            seconds = seconds < 10 ? "0" + seconds : seconds;

            $('#timer').text(minutes + ":" + seconds);

            if (--timer < 0) {
                clearInterval(timerInterval);
                $('#timer').text("Time expired. Please request a new code.");
                $('#confirmBtn').prop('disabled', true);
            }
        }, 1000);
    }

    // Handle Send Verification Button Click
    // $('#sendVerificationBtn').on('click', function() {
    //     var email = $('#email').val();
    //     if (email) {
    //         // Disable the button and show loading indicator
    //         $('#sendVerificationBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Sending...');

    //         // Send AJAX request to the server for sending the verification code
    //         $.ajax({
    //             url: '/Assesment-Templates/backend/send_code.php',
    //             type: 'POST',
    //             data: { email: email },
    //             dataType: 'json',
    //             success: function(response) {
    //                 if (response.status === 'success') {
    //                     verificationSent = true;
    //                     // Show the verification section
    //                     $('#verificationSection').show();
    //                     $('#verificationCode').prop('required', true); // Add required attribute
    //                     $('#confirmBtn').prop('disabled', false); // Enable confirm button
    //                     startTimer(300); // Start a 5-minute timer
    //                     alert(response.message);
    //                 } else {
    //                     alert('Failed to send verification code: ' + response.message);
    //                 }
    //             },
    //             error: function(jqXHR, textStatus, errorThrown) {
    //                 console.error('AJAX error:', textStatus, errorThrown);
    //                 console.log('Response Text:', jqXHR.responseText); // Log the full response for debugging
    //                 alert('Error: Could not reach the server. Please try again later.');
    //             },
    //             complete: function() {
    //                 // Re-enable the button and restore original text
    //                 $('#sendVerificationBtn').prop('disabled', false).text('Send Verification Code');
    //             }
    //         });
    //     } else {
    //         alert('Please enter a valid email address.');
    //     }
    // });
    
    
    
    // Handle Send Verification Button Click
// $('#sendVerificationBtn').on('click', function() {
//     var email = $('#email').val();
//     var firstName = $('#first-name').val().trim(); // Get first name

//     // Validate both email and first name
//     if (!email) {
//         alert('Please enter a valid email address.');
//         return;
//     }
//     if (!firstName) {
//         alert('Please enter your first name.');
//         return;
//     }

//     // Disable the button and show loading indicator
//     $('#sendVerificationBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Sending...');
    
//     // Send AJAX request to the server for sending the verification code
//     $.ajax({
//         url: '/Assesment-Templates/backend/send_code.php',
//         type: 'POST',
//         data: { 
//             email: email,
//             first_name: firstName // Include first name in the data
//         },
//         dataType: 'json',
//         success: function(response) {
//             if (response.status === 'success') {
//                 verificationSent = true;
//                 // Show the verification section
//                 $('#verificationSection').show();
//                 $('#verificationCode').prop('required', true); // Add required attribute
//                 $('#confirmBtn').prop('disabled', false); // Enable confirm button
//                 startTimer(300); // Start a 5-minute timer
//                 alert(response.message);
//             } else {
//                 alert('Failed to send verification code: ' + response.message);
//             }
//         },
//         error: function(jqXHR, textStatus, errorThrown) {
//             console.error('AJAX error:', textStatus, errorThrown);
//             console.log('Response Text:', jqXHR.responseText); // Log the full response for debugging
//             alert('Error: Could not reach the server. Please try again later.');
//         },
//         complete: function() {
//             // Re-enable the button and restore original text
//             $('#sendVerificationBtn').prop('disabled', false).text('Send Verification Code');
//         }
//     });
// });

// Handle Send Verification Button Click
// Handle Send Verification Button Click


// Handle Send Verification Button Click
// Handle Send Verification Button Click
$('#sendVerificationBtn').on('click', function() {
    var email = $('#email').val().trim();
    var firstName = $('#first-name').val().trim();

    console.log('Sending verification to:', {
        email: email,
        firstName: firstName
    });

    // Show loading state
    $('#sendVerificationBtn').prop('disabled', true)
        .html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Sending...');

    $.ajax({
        url: '/Assesment-Templates/backend/send_code.php',
        type: 'POST',
        data: {
            email: email,
            first_name: firstName
        },
        success: function(response) {
            console.log('Server Response:', response);
            // If email was sent successfully, show verification section
            verificationSent = true;
            $('#verificationSection').show();
            $('#verificationCode').prop('required', true);
            $('#confirmBtn').prop('disabled', false);
            startTimer(300);
            alert("Verification code has been sent to your email address");
        },
        error: function(xhr, status, error) {
            // Even if there's an error, if we know the email was sent, proceed
            if (xhr.responseText && xhr.responseText.includes('email sent')) {
                verificationSent = true;
                $('#verificationSection').show();
                $('#verificationCode').prop('required', true);
                $('#confirmBtn').prop('disabled', false);
                startTimer(300);
                alert("Verification code has been sent to your email address");
            } else {
                console.error('AJAX Error:', {
                    status: status,
                    error: error,
                    responseText: xhr.responseText
                });
                alert('There was an error, but your verification code may have been sent. Please check your email.');
            }
        },
        complete: function() {
            // Re-enable the button and restore text
            $('#sendVerificationBtn').prop('disabled', false)
                .text('Send Verification Code');
        }
    });
});





    // Handle Confirm Button Click
    // $('#confirmBtn').on('click', function() {
    //     var code = $('#verificationCode').val();
    //     if (code) {
    //         // Send AJAX request to verify the code
    //         $.ajax({
    //             url: '/Assesment-Templates/backend/verify_code.php',
    //             type: 'POST',
    //             data: { verification_code: code },
    //             dataType: 'json',
    //             success: function(response) {
    //                 if (response.status === 'success') {
    //                     verificationConfirmed = true;
    //                     $('#successModal').modal('show'); // Show success modal
    //                     clearInterval(timerInterval); // Stop the timer
    //                 } else {
    //                     alert('Invalid code. Please try again.');
    //                 }
    //             },
    //             error: function(jqXHR, textStatus, errorThrown) {
    //                 console.error('AJAX error:', textStatus, errorThrown);
    //                 console.log('Response Text:', jqXHR.responseText);
    //                 alert('Error: Could not reach the server. Please try again later.');
    //             }
    //         });
    //     } else {
    //         alert('Please enter the verification code.');
    //     }
    // });


    // Form submission function
    // function submitForm() {
    //     var formData = $('#userInfoForm').serialize();

    //     $.ajax({
    //         url: '/Assesment-Templates/backend/process.php',
    //         type: 'POST',
    //         data: formData,
    //         dataType: 'json',
    //         success: function(response) {
    //             if (response.success) {
    //                 window.location.href = '/Assesment-Templates/frontend/templates/step-1.html';
    //             } else {
    //                 alert('Error submitting form: ' + response.message);
    //             }
    //         },
    //         error: function(jqXHR, textStatus, errorThrown) {
    //             console.error('AJAX error:', textStatus, errorThrown);
    //             console.log('Response Text:', jqXHR.responseText);
    //             alert('Error: Could not reach the server. Please try again later.');
    //         }
    //     });
    // }

    // Hide the verification section initially and remove the required attribute
    $('#verificationSection').hide();
    $('#verificationCode').prop('required', false);
});
