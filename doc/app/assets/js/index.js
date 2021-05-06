
document.getElementsByClassName('sidebar').forEach(element => {
    element.addEventListener('click', event => {
        if (event.target.tagName !== 'A') {
            element.classList.toggle('active');
        }
    })
})
