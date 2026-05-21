// Menú desplegable en móvil
const menuIcon = document.getElementById('icono-menu');
const navbar = document.querySelector('.navbar');

// Abre/cierra el menú al hacer clic en el icono del menú
menuIcon.onclick = () => {
    navbar.classList.toggle('active');
};

// Cierra el menú al hacer clic en cualquier enlace dentro de navbar
const navbarLinks = document.querySelectorAll('.navbar a');

navbarLinks.forEach(link => {
    link.onclick = () => {
        navbar.classList.remove('active');
    };
});

// Configuración de Swiper
var swiper = new Swiper(".mySwiper", {
    slidesPerView: 1,
    spaceBetween: 20,
    loop: true,
    pagination: {
        el: ".swiper-pagination",
        clickable: true,
    },
});
