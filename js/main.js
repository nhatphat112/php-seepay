/**
 * Silkroad Origin Mobile - Main JavaScript
 */

// Smooth scroll for navigation links
document.querySelectorAll('.nav-link').forEach(link => {
    link.addEventListener('click', function(e) {
        e.preventDefault();
        const targetId = this.getAttribute('href');
        const targetSection = document.querySelector(targetId);
        
        if (targetSection) {
            targetSection.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Update active nav link on scroll
const sections = document.querySelectorAll('section[id]');
const navLinks = document.querySelectorAll('.nav-link');

window.addEventListener('scroll', () => {
    let current = '';
    
    sections.forEach(section => {
        const sectionTop = section.offsetTop;
        const sectionHeight = section.clientHeight;
        
        if (window.pageYOffset >= sectionTop - 200) {
            current = section.getAttribute('id');
        }
    });
    
    navLinks.forEach(link => {
        link.classList.remove('active');
        if (link.getAttribute('href') === '#' + current) {
            link.classList.add('active');
        }
    });
});

// News tabs functionality
const newsTabs = document.querySelectorAll('.news-tab');
newsTabs.forEach(tab => {
    tab.addEventListener('click', function() {
        newsTabs.forEach(t => t.classList.remove('active'));
        this.classList.add('active');
        
        // Filter news based on tab
        const tabText = this.textContent;
        console.log('Filter news:', tabText);
    });
});

// Parallax effect for hero background
window.addEventListener('scroll', function() {
    const heroSection = document.querySelector('.hero-section');
    if (heroSection) {
        const scrolled = window.pageYOffset;
        const bgImage = document.querySelector('.bg-image');
        if (bgImage && scrolled < window.innerHeight) {
            bgImage.style.transform = 'translateY(' + scrolled * 0.5 + 'px)';
        }
    }
});

// Scroll animations
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver(function(entries) {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
        }
    });
}, observerOptions);

// Observe gameplay cards
document.querySelectorAll('.gameplay-card').forEach(card => {
    card.style.opacity = '0';
    card.style.transform = 'translateY(30px)';
    card.style.transition = 'opacity 0.6s, transform 0.6s';
    observer.observe(card);
});

// Observe news items
document.querySelectorAll('.news-item').forEach(item => {
    item.style.opacity = '0';
    item.style.transform = 'translateX(-30px)';
    item.style.transition = 'opacity 0.5s, transform 0.5s';
    observer.observe(item);
});

// Button click handlers
document.querySelectorAll('.download-main-btn, .btn-download').forEach(btn => {
    btn.addEventListener('click', function() {
        alert('Tải game tại: https://silkroadorigin.com');
    });
});

document.querySelectorAll('.btn-support').forEach(btn => {
    btn.addEventListener('click', function() {
        alert('Liên hệ hỗ trợ: support@silkroadorigin.com');
    });
});

document.querySelectorAll('.btn-topup, .action-hexagon').forEach(btn => {
    btn.addEventListener('click', function() {
        const text = this.textContent.trim();
        if (text.includes('NẠP TIỀN')) {
            alert('Nạp tiền tại: https://silkroadorigin.com/topup');
        } else if (text.includes('TIẾN TRIỂN')) {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        } else if (text.includes('HƯỚNG DẪN')) {
            document.querySelector('#gameplay').scrollIntoView({ behavior: 'smooth' });
        } else if (text.includes('ỨNG HỖ')) {
            alert('Ủng hộ server: https://silkroadorigin.com/donate');
        }
    });
});

// Career character hover effect
document.querySelectorAll('.career-character').forEach(character => {
    character.addEventListener('mouseenter', function() {
        this.style.zIndex = '100';
    });
    
    character.addEventListener('mouseleave', function() {
        this.style.zIndex = 'auto';
    });
});

// News item click
document.querySelectorAll('.news-item').forEach(item => {
    item.addEventListener('click', function() {
        const title = this.querySelector('.news-title').textContent;
        alert('Đọc tin: ' + title);
    });
});

// Navbar scroll effect
let lastScroll = 0;
const navbar = document.querySelector('.main-nav');

window.addEventListener('scroll', () => {
    const currentScroll = window.pageYOffset;
    
    if (currentScroll > 100) {
        navbar.style.background = 'rgba(0, 0, 0, 0.98)';
        navbar.style.boxShadow = '0 2px 20px rgba(212, 165, 116, 0.3)';
    } else {
        navbar.style.background = 'rgba(0, 0, 0, 0.95)';
        navbar.style.boxShadow = 'none';
    }
    
    lastScroll = currentScroll;
});

// Bottom icons click
document.querySelectorAll('.bottom-icon-item').forEach(item => {
    item.addEventListener('click', function() {
        const label = this.querySelector('.label').textContent;
        alert('Chức năng: ' + label);
    });
});

// Console welcome message
console.log('%c🐉 SILKROAD ORIGIN MOBILE', 'color: #d4a574; font-size: 24px; font-weight: bold;');
console.log('%cWebsite Version 2.1 | Build: 2025.10.02', 'color: #999; font-size: 12px;');

// Auto scroll to section from URL hash
if (window.location.hash) {
    setTimeout(() => {
        const target = document.querySelector(window.location.hash);
        if (target) {
            target.scrollIntoView({ behavior: 'smooth' });
        }
    }, 100);
}

// Add loading animation on page load
window.addEventListener('load', () => {
    document.body.style.opacity = '0';
    setTimeout(() => {
        document.body.style.transition = 'opacity 0.5s';
        document.body.style.opacity = '1';
    }, 100);
});
