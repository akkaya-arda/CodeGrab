<script>
    window.opener.postMessage({
        success: true,
        message: 'Google account saved successfully.',
    }, '*');
    window.close();
</script>