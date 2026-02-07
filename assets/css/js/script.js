function sharePage() {
    let url = window.location.href;
    window.open(
        "https://www.facebook.com/sharer/sharer.php?u=" + encodeURIComponent(url),
        "_blank"
    );
}
