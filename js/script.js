// simple passcode prompt before submitting any transaction form
function requirePasscode(form) {
    var pass = prompt("Enter your 4-digit passcode to authorize this transaction:");
    if (pass === null) {
        return false; // cancelled
    }
    var passInput = document.createElement('input');
    passInput.type = 'hidden';
    passInput.name = 'passcode';
    passInput.value = pass;
    form.appendChild(passInput);
    return true;
}

// attach to forms automatically if they have data-require-pass="true"
document.addEventListener('DOMContentLoaded', function() {
    var forms = document.querySelectorAll('form[data-require-pass]');
    forms.forEach(function(f) {
        f.addEventListener('submit', function(e) {
            if (!requirePasscode(f)) {
                e.preventDefault();
            }
        });
    });
});