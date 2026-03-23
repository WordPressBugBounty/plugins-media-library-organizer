/**
 * MLO Gallery Frontend Scripts
 * Handles Lightbox, Filter Bar, and Carousel initializations.
 */

document.addEventListener('DOMContentLoaded', () => {
	const galleries = document.querySelectorAll('.mlo-gallery-block');

	galleries.forEach(gallery => {
		initFilterBar(gallery);
		initLightbox(gallery);
		initCarousel(gallery);
		initJustified(gallery); // Since justified may need JS for perfect rows if flex-grow isn't enough
		initMasonry(gallery); // Initialize masonry layout
	});
});

/**
 * Filter Bar Logic
 */
function initFilterBar(gallery) {
	const filterBar = gallery.querySelector('.mlo-filter-bar');
	if (!filterBar) return;

	const items = gallery.querySelectorAll('.mlo-gallery-item');
	const animation = gallery.getAttribute('data-animation') || 'fade';
	const style = filterBar.classList.contains('mlo-filter-dropdown') ? 'dropdown' : 'pills';

	const filterItems = (catId) => {
		let visibleCount = 0;
		
		items.forEach(item => {
			const catsAttr = item.getAttribute('data-cats') || '';
			const itemCats = catsAttr.trim().split(/\s+/).filter(Boolean);
			const match = catId === 'all' || itemCats.includes(String(catId));

			if (match) visibleCount++;

			if (animation === 'none') {
				item.classList.toggle('mlo-item-hidden', !match);
			} else {
				if (match) {
					item.classList.remove('mlo-item-hidden');
					// Small timeout to allow display:block to apply before animating opacity
					setTimeout(() => item.classList.remove('mlo-anim-hide'), 10);
				} else {
					item.classList.add('mlo-anim-hide');
					// Wait for transition to finish before display:none
					setTimeout(() => item.classList.add('mlo-item-hidden'), 400); 
				}
			}
		});

		// Show/hide empty message
		const emptyMessage = gallery.querySelector('.mlo-filter-empty-message');
		if (emptyMessage) {
			if (visibleCount === 0) {
				emptyMessage.style.display = 'block';
			} else {
				emptyMessage.style.display = 'none';
			}
		}

		// Trigger relayout for carousel/justified if needed after filtering
		setTimeout(() => {
			if (gallery.classList.contains('mlo-gallery-layout-carousel')) {
				updateCarouselVisually(gallery);
			}
			if (gallery.classList.contains('mlo-gallery-layout-justified')) {
				initJustified(gallery);
			}
		}, 450);
	};

	if (style === 'pills') {
		const buttons = filterBar.querySelectorAll('.mlo-filter-btn');
		buttons.forEach(btn => {
			btn.addEventListener('click', () => {
				buttons.forEach(b => b.classList.remove('active'));
				btn.classList.add('active');
				filterItems(btn.getAttribute('data-cat'));
			});
		});
	} else if (style === 'dropdown') {
		const select = filterBar.querySelector('.mlo-filter-select');
		select.addEventListener('change', (e) => {
			filterItems(e.target.value);
		});
	}
}

/**
 * Lightbox Logic
 */
function initLightbox(gallery) {
	if (gallery.getAttribute('data-lightbox') !== '1') return;

	const triggers = gallery.querySelectorAll('.mlo-lightbox-trigger');
	const overlay = gallery.querySelector('.mlo-lightbox-overlay');
	if (!overlay) return;

	const closeBtn = overlay.querySelector('.mlo-lightbox-close');
	const prevBtn = overlay.querySelector('.mlo-lightbox-prev');
	const nextBtn = overlay.querySelector('.mlo-lightbox-next');
	const img = overlay.querySelector('.mlo-lightbox-img');
	const caption = overlay.querySelector('.mlo-lightbox-caption');
	const counter = overlay.querySelector('.mlo-lightbox-counter');
	
	let currentIndex = 0;
	let visibleItems = []; // Array of triggers currently visible (not filtered out)

	const updateVisibleItems = () => {
		visibleItems = Array.from(triggers).filter(t => !t.closest('.mlo-gallery-item').classList.contains('mlo-item-hidden'));
	};

	const showImage = (index) => {
		if (visibleItems.length === 0) return;
		if (index < 0) index = visibleItems.length - 1;
		if (index >= visibleItems.length) index = 0;
		currentIndex = index;

		const trigger = visibleItems[index];
		img.src = trigger.href;
		
		if (caption) {
			caption.textContent = trigger.getAttribute('data-caption') || '';
		}
		if (counter) {
			counter.textContent = `${index + 1} of ${visibleItems.length}`;
		}
	};

	// Keyboard navigation handler - bound/unbound on lightbox open/close
	const handleKeydown = (e) => {
		if (e.key === 'Escape') closeLightbox();
		if (e.key === 'ArrowLeft') showImage(currentIndex - 1);
		if (e.key === 'ArrowRight') showImage(currentIndex + 1);
	};

	const openLightbox = () => {
		overlay.classList.add('active');
		overlay.setAttribute('aria-hidden', 'false');
		document.body.classList.add('mlo-lightbox-open');
		document.addEventListener('keydown', handleKeydown);
	};

	const closeLightbox = () => {
		overlay.classList.remove('active');
		overlay.setAttribute('aria-hidden', 'true');
		document.body.classList.remove('mlo-lightbox-open');
		document.removeEventListener('keydown', handleKeydown);
	};

	triggers.forEach(trigger => {
		trigger.addEventListener('click', (e) => {
			e.preventDefault();
			updateVisibleItems();
			currentIndex = visibleItems.indexOf(trigger);
			if(currentIndex === -1) currentIndex = 0;
			showImage(currentIndex);
			openLightbox();
		});
	});

	if (closeBtn) closeBtn.addEventListener('click', closeLightbox);
	overlay.addEventListener('click', (e) => {
		if (e.target === overlay || e.target.classList.contains('mlo-lightbox-inner') || e.target.classList.contains('mlo-lightbox-img-wrap')) {
			closeLightbox();
		}
	});

	if (prevBtn) prevBtn.addEventListener('click', () => showImage(currentIndex - 1));
	if (nextBtn) nextBtn.addEventListener('click', () => showImage(currentIndex + 1));
}

/**
 * Carousel Logic
 */
function initCarousel(gallery) {
	if (!gallery.classList.contains('mlo-gallery-layout-carousel')) return;

	const track = gallery.querySelector('.mlo-carousel-track');
	const prevBtn = gallery.querySelector('.mlo-carousel-prev');
	const nextBtn = gallery.querySelector('.mlo-carousel-next');
	const dotsContainer = gallery.querySelector('.mlo-carousel-dots');
	
	if (!track) return;
	
	let slides = Array.from(track.querySelectorAll('.mlo-carousel-slide'));
	let currentIndex = 0;
	let visibleSlides = parseInt(gallery.getAttribute('data-slides-visible'), 10) || 1;
	const autoplay = gallery.getAttribute('data-autoplay') === '1';
	const autoplaySpeed = parseInt(gallery.getAttribute('data-autoplay-speed'), 10) || 3000;
	const gap = parseInt(window.getComputedStyle(gallery).getPropertyValue('--mlo-gap'), 10) || 10;
	let autoplayInterval;
	
	// If total slides <= visible slides, no need for carousel functionality
	if (slides.length <= visibleSlides) {
		// Hide navigation controls
		if (prevBtn) prevBtn.style.display = 'none';
		if (nextBtn) nextBtn.style.display = 'none';
		if (dotsContainer) dotsContainer.style.display = 'none';
		
		// Just set the slide widths and return
		const containerWidth = track.parentElement.offsetWidth;
		const totalGap = gap * (slides.length - 1);
		const slideWidth = (containerWidth - totalGap) / slides.length;
		slides.forEach(slide => {
			slide.style.width = `${slideWidth}px`;
			slide.style.flexShrink = '0';
		});
		
		return;
	}

	// Responsive override for visible slides
	const updateVisibleCount = () => {
		if (window.innerWidth <= 480) {
			return 1;
		} else if (window.innerWidth <= 768) {
			return Math.min(2, visibleSlides);
		}
		return visibleSlides;
	};

	let actualVisible = updateVisibleCount();

	// Set CSS variable
	gallery.style.setProperty('--mlo-slides-visible', actualVisible);

	const update = () => {
		actualVisible = updateVisibleCount();
		gallery.style.setProperty('--mlo-slides-visible', actualVisible);
		
		// Filter out hidden slides
		const activeSlides = slides.filter(s => !s.classList.contains('mlo-item-hidden'));
		const maxIndex = Math.max(0, activeSlides.length - actualVisible);
		
		// If active slides <= visible slides after filtering, hide controls
		if (activeSlides.length <= actualVisible) {
			if (prevBtn) prevBtn.style.display = 'none';
			if (nextBtn) nextBtn.style.display = 'none';
			if (dotsContainer) dotsContainer.style.display = 'none';
			
			// Just set the slide widths without carousel functionality
			const containerWidth = track.parentElement.offsetWidth;
			const totalGap = gap * (activeSlides.length - 1);
			const slideWidth = activeSlides.length > 0 ? (containerWidth - totalGap) / activeSlides.length : 0;
			slides.forEach(slide => {
				if (!slide.classList.contains('mlo-item-hidden')) {
					slide.style.width = `${slideWidth}px`;
					slide.style.flexShrink = '0';
				}
			});
			track.style.transform = `translateX(0)`;
			return;
		}
		
		// Show controls if we have enough slides
		if (prevBtn) prevBtn.style.display = 'flex';
		if (nextBtn) nextBtn.style.display = 'flex';
		if (dotsContainer) dotsContainer.style.display = 'block';
		
		if (currentIndex > maxIndex) currentIndex = maxIndex;

		// Calculate translation
		// Slide width is (100% - (gap * (visible - 1))) / visible
		// We translate by (Slide Width + Gap) * currentIndex
		if (activeSlides.length > 0) {
			const containerWidth = track.parentElement.offsetWidth;
			const totalGap = gap * (actualVisible - 1);
			const slideWidth = (containerWidth - totalGap) / actualVisible;
			const transform = (slideWidth + gap) * currentIndex;
			track.style.transform = `translateX(-${transform}px)`;
			
			// Update each slide width explicitly
			slides.forEach(slide => {
				slide.style.width = `${slideWidth}px`;
			});
		} else {
			track.style.transform = `translateX(0)`;
		}

		// Update dots
		if (dotsContainer) {
			const dotCount = Math.max(1, activeSlides.length - actualVisible + 1);
			dotsContainer.innerHTML = '';
			for (let i = 0; i < dotCount; i++) {
				const dot = document.createElement('span');
				dot.className = `mlo-carousel-dot ${i === currentIndex ? 'active' : ''}`;
				dot.addEventListener('click', () => {
					currentIndex = i;
					update();
					resetAutoplay();
				});
				dotsContainer.appendChild(dot);
			}
		}

		// Update button states
		if (prevBtn) prevBtn.style.opacity = currentIndex === 0 ? '0.5' : '1';
		if (nextBtn) nextBtn.style.opacity = currentIndex >= maxIndex ? '0.5' : '1';
	};

	window.addEventListener('resize', update);
	update();

	// Expose update function for filter
	gallery.updateCarousel = update;

	if (prevBtn) {
		prevBtn.addEventListener('click', () => {
			if (currentIndex > 0) {
				currentIndex--;
				update();
				resetAutoplay();
			}
		});
	}

	if (nextBtn) {
		nextBtn.addEventListener('click', () => {
			const activeSlides = slides.filter(s => !s.classList.contains('mlo-item-hidden'));
			const maxIndex = Math.max(0, activeSlides.length - actualVisible);
			if (currentIndex < maxIndex) {
				currentIndex++;
				update();
				resetAutoplay();
			} else if (autoplay) {
				currentIndex = 0; // wrap around on autoplay
				update();
			}
		});
	}

	const startAutoplay = () => {
		if (autoplay) {
			autoplayInterval = setInterval(() => {
				const activeSlides = slides.filter(s => !s.classList.contains('mlo-item-hidden'));
				const maxIndex = Math.max(0, activeSlides.length - actualVisible);
				if (currentIndex < maxIndex) {
					currentIndex++;
				} else {
					currentIndex = 0;
				}
				update();
			}, autoplaySpeed);
		}
	};

	const stopAutoplay = () => clearInterval(autoplayInterval);
	const resetAutoplay = () => {
		stopAutoplay();
		startAutoplay();
	};

	if (autoplay) {
		startAutoplay();
		gallery.addEventListener('mouseenter', stopAutoplay);
		gallery.addEventListener('mouseleave', startAutoplay);
	}
}

function updateCarouselVisually(gallery) {
	if (gallery.updateCarousel) gallery.updateCarousel();
}

/**
 * Justified Gallery Layout
 * Creates rows with consistent height and properly sized images
 */
function initJustified(gallery) {
	if (!gallery.classList.contains('mlo-gallery-layout-justified')) return;
	
	const performLayout = () => {
		const track = gallery.querySelector('.mlo-gallery-grid');
		if (!track) return;
		
		const items = Array.from(track.querySelectorAll('.mlo-gallery-item:not(.mlo-item-hidden)'));
		if (items.length === 0) return;
		
		// Wait for images to load to calculate aspect ratios
		const imageLoadPromises = items.map(item => {
			return new Promise(resolve => {
				const img = item.querySelector('img');
				if (img && img.complete && img.naturalWidth) {
					resolve();
				} else if (img) {
					img.onload = () => resolve();
					img.onerror = () => resolve();
				} else {
					resolve();
				}
			});
		});
		
		Promise.all(imageLoadPromises).then(() => {
			const targetHeight = parseInt(window.getComputedStyle(gallery).getPropertyValue('--mlo-row-height'), 10) || 250;
			
			items.forEach(item => {
				const img = item.querySelector('img');
				if (img && img.naturalWidth && img.naturalHeight) {
					const ratio = img.naturalWidth / img.naturalHeight;
					const width = targetHeight * ratio;
					
					// Set flex properties for proper row distribution
					item.style.flexBasis = `${width}px`;
					item.style.flexGrow = `${ratio}`;
					item.style.flexShrink = '1';
					item.style.height = `${targetHeight}px`;
					
					// Ensure image fills container
					img.style.width = '100%';
					img.style.height = '100%';
					img.style.objectFit = 'cover';
				}
			});
		});
	};
	
	// Initial layout
	performLayout();
	
	// Re-layout on resize (attach only once per gallery)
	if (!gallery._mloJustifiedResizeHandler) {
		let resizeTimeout;
		const handler = () => {
			clearTimeout(resizeTimeout);
			resizeTimeout = setTimeout(performLayout, 250);
		};
		
		gallery._mloJustifiedResizeHandler = handler;
		window.addEventListener('resize', handler);
	}
}

/**
 * Masonry Layout
 * Ensures images are loaded before displaying
 */
function initMasonry(gallery) {
	if (!gallery.classList.contains('mlo-gallery-layout-masonry')) return;
	
	const track = gallery.querySelector('.mlo-gallery-grid');
	if (!track) return;
	
	const items = Array.from(track.querySelectorAll('.mlo-gallery-item'));
	if (items.length === 0) return;
	
	// Set initial opacity to 0 to prevent layout jump
	track.style.opacity = '0';
	
	// Wait for all images to load
	const imageLoadPromises = items.map(item => {
		return new Promise(resolve => {
			const img = item.querySelector('img');
			if (img && img.complete) {
				resolve();
			} else if (img) {
				img.onload = () => resolve();
				img.onerror = () => resolve();
			} else {
				resolve();
			}
		});
	});
	
	Promise.all(imageLoadPromises).then(() => {
		// Fade in the grid once all images are loaded
		track.style.opacity = '1';
	});
}

