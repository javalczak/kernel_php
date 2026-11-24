</main>
<?php /* Replaces the onsubmit attribute, which the content security policy blocks. */ ?>
<script nonce="<?= CSP_NONCE ?>">
    document.addEventListener('submit', function (event) {
        var message = event.target.getAttribute('data-confirm');

        if (message && !window.confirm(message)) {
            event.preventDefault();
        }
    });
</script>
</body>
</html>
