var scrollPosition = window.scrollY,
    siteHeader = document.getElementsByClassName('site-header')[0],
    siteHeaderHeight = siteHeader.offsetHeight;

window.addEventListener('scroll', function () {

    scrollPosition = window.scrollY;

    if (scrollPosition >= siteHeaderHeight) {
        siteHeader.classList.add('sticky');
    } else {
        siteHeader.classList.remove('sticky');
    }

});

if (document.body.classList.contains('has-sticky-header')) {
	console.log(siteHeader);
	console.log(siteHeaderHeight + 'px');
    siteHeader.style.marginTop(siteHeaderHeight + 'px');
}
