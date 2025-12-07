/**
 * SwapIt Rating & Review System
 * Handles user ratings and reviews
 * @version 1.0
 */

class RatingSystem {
    constructor() {
        this.currentRequestId = null;
        this.currentRating = 0;
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.loadUserRatings();
    }

    setupEventListeners() {
        // Star rating hover and click
        document.querySelectorAll('.star-rating').forEach(container => {
            const stars = container.querySelectorAll('.star');
            stars.forEach((star, index) => {
                star.addEventListener('mouseenter', () => {
                    this.highlightStars(stars, index + 1);
                });
                star.addEventListener('click', () => {
                    this.selectRating(container, index + 1);
                });
            });

            container.addEventListener('mouseleave', () => {
                const rating = parseInt(container.dataset.rating || 0);
                this.highlightStars(stars, rating);
            });
        });

        // Review form submission
        const reviewForm = document.getElementById('review-form');
        if (reviewForm) {
            reviewForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.submitReview();
            });
        }

        // Rate buttons on completed requests
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('rate-user-btn')) {
                const requestId = e.target.dataset.requestId;
                const userId = e.target.dataset.userId;
                const reviewType = e.target.dataset.reviewType;
                this.showReviewModal(requestId, userId, reviewType);
            }
        });

        // Load more reviews
        const loadMoreBtn = document.getElementById('load-more-reviews');
        if (loadMoreBtn) {
            loadMoreBtn.addEventListener('click', () => this.loadMoreReviews());
        }
    }

    highlightStars(stars, count) {
        stars.forEach((star, index) => {
            if (index < count) {
                star.classList.add('active');
            } else {
                star.classList.remove('active');
            }
        });
    }

    selectRating(container, rating) {
        container.dataset.rating = rating;
        this.currentRating = rating;
        const stars = container.querySelectorAll('.star');
        this.highlightStars(stars, rating);
    }

    async showReviewModal(requestId, userId, reviewType) {
        // First check if user can review
        try {
            const response = await fetch(
                `/api/ratings.php?action=can_review&request_id=${requestId}`,
                { credentials: 'include' }
            );

            const data = await response.json();

            if (!data.success || !data.can_review) {
                alert(data.reason || 'Cannot review this transaction');
                return;
            }

            this.currentRequestId = requestId;
            const modal = document.getElementById('review-modal');
            if (!modal) return;

            // Set hidden fields
            document.getElementById('review-request-id').value = requestId;
            document.getElementById('review-user-id').value = data.reviewed_user_id;
            document.getElementById('review-type').value = data.review_type;

            modal.style.display = 'block';
        } catch (error) {
            console.error('Error checking review eligibility:', error);
            alert('Failed to open review form');
        }
    }

    async submitReview() {
        const form = document.getElementById('review-form');
        const formData = new FormData(form);
        formData.append('action', 'submit_review');
        formData.append('rating', this.currentRating);

        if (this.currentRating === 0) {
            alert('Please select a rating');
            return;
        }

        const submitBtn = form.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';

        try {
            const response = await fetch('/api/ratings.php', {
                method: 'POST',
                credentials: 'include',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                this.showNotification('Review submitted successfully!', 'success');
                this.closeModal('review-modal');
                form.reset();
                this.currentRating = 0;
                
                // Reset star rating display
                const stars = document.querySelectorAll('#review-modal .star');
                this.highlightStars(stars, 0);
                
                // Reload reviews if on profile page
                if (window.location.pathname.includes('profile')) {
                    await this.loadUserRatings();
                }
            } else {
                this.showNotification(data.error, 'error');
            }
        } catch (error) {
            console.error('Error submitting review:', error);
            this.showNotification('Failed to submit review. Please try again.', 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-star"></i> Submit Review';
        }
    }

    async loadUserRatings(userId = null, limit = 10, offset = 0) {
        try {
            const url = userId 
                ? `/api/ratings.php?action=get_user_reviews&user_id=${userId}&limit=${limit}&offset=${offset}`
                : `/api/ratings.php?action=get_user_reviews&limit=${limit}&offset=${offset}`;

            const response = await fetch(url, { credentials: 'include' });
            const data = await response.json();

            if (data.success) {
                this.renderReviews(data.reviews);
                this.renderRatingDistribution(data.distribution);
            }
        } catch (error) {
            console.error('Error loading ratings:', error);
        }
    }

    renderReviews(reviews) {
        const container = document.getElementById('reviews-container');
        if (!container) return;

        if (reviews.length === 0) {
            container.innerHTML = `
                <div class="no-reviews">
                    <i class="fas fa-star"></i>
                    <p>No reviews yet</p>
                </div>
            `;
            return;
        }

        container.innerHTML = reviews.map(review => `
            <div class="review-card">
                <div class="review-header">
                    <div class="reviewer-info">
                        <img src="${review.reviewer_avatar || '/public/assets/images/default-avatar.png'}" 
                             alt="${review.display_name}" class="reviewer-avatar">
                        <div>
                            <h4>${review.display_name}</h4>
                            <div class="review-rating">
                                ${this.renderStars(review.rating)}
                            </div>
                        </div>
                    </div>
                    <span class="review-date">${this.formatDate(review.created_at)}</span>
                </div>

                ${review.title ? `<h3 class="review-title">${this.escapeHtml(review.title)}</h3>` : ''}

                ${review.comment ? `
                    <p class="review-comment">${this.escapeHtml(review.comment)}</p>
                ` : ''}

                <div class="review-type-badge ${review.review_type}">
                    ${review.review_type === 'borrower_to_lender' ? 'As Borrower' : 'As Lender'}
                </div>
            </div>
        `).join('');
    }

    renderRatingDistribution(distribution) {
        const container = document.getElementById('rating-distribution');
        if (!container) return;

        // Calculate total reviews
        const total = distribution.reduce((sum, d) => sum + parseInt(d.count), 0);
        
        if (total === 0) {
            container.innerHTML = '<p>No ratings yet</p>';
            return;
        }

        // Create distribution bars
        const bars = [5, 4, 3, 2, 1].map(rating => {
            const item = distribution.find(d => d.rating == rating);
            const count = item ? parseInt(item.count) : 0;
            const percentage = (count / total * 100).toFixed(1);

            return `
                <div class="rating-bar">
                    <span class="rating-label">${rating} <i class="fas fa-star"></i></span>
                    <div class="bar-container">
                        <div class="bar-fill" style="width: ${percentage}%"></div>
                    </div>
                    <span class="rating-count">${count}</span>
                </div>
            `;
        }).join('');

        // Calculate average
        const average = distribution.reduce((sum, d) => 
            sum + (parseInt(d.rating) * parseInt(d.count)), 0) / total;

        container.innerHTML = `
            <div class="rating-summary">
                <div class="average-rating">
                    <span class="rating-number">${average.toFixed(1)}</span>
                    <div class="rating-stars">${this.renderStars(Math.round(average))}</div>
                    <span class="total-reviews">${total} review${total !== 1 ? 's' : ''}</span>
                </div>
            </div>
            <div class="distribution-bars">
                ${bars}
            </div>
        `;
    }

    renderStars(rating) {
        let stars = '';
        for (let i = 1; i <= 5; i++) {
            if (i <= rating) {
                stars += '<i class="fas fa-star"></i>';
            } else if (i - 0.5 <= rating) {
                stars += '<i class="fas fa-star-half-alt"></i>';
            } else {
                stars += '<i class="far fa-star"></i>';
            }
        }
        return stars;
    }

    async loadRatingStats(userId = null) {
        try {
            const url = userId
                ? `/api/ratings.php?action=get_rating_stats&user_id=${userId}`
                : `/api/ratings.php?action=get_rating_stats`;

            const response = await fetch(url, { credentials: 'include' });
            const data = await response.json();

            if (data.success) {
                this.displayRatingStats(data.stats);
            }
        } catch (error) {
            console.error('Error loading rating stats:', error);
        }
    }

    displayRatingStats(stats) {
        const container = document.getElementById('rating-stats');
        if (!container) return;

        container.innerHTML = `
            <div class="stat-card">
                <i class="fas fa-star"></i>
                <div class="stat-value">${parseFloat(stats.average_rating).toFixed(1)}</div>
                <div class="stat-label">Average Rating</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-comments"></i>
                <div class="stat-value">${stats.total_reviews}</div>
                <div class="stat-label">Total Reviews</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-thumbs-up"></i>
                <div class="stat-value">${stats.five_star}</div>
                <div class="stat-label">5-Star Reviews</div>
            </div>
        `;
    }

    async checkPendingReviews() {
        // This could be called on dashboard to show requests that need review
        try {
            const response = await fetch(
                '/api/requests.php?action=get_my_requests&status=completed',
                { credentials: 'include' }
            );

            const data = await response.json();

            if (data.success) {
                // Filter requests that haven't been reviewed yet
                // This would require checking against existing reviews
                this.displayPendingReviews(data.requests);
            }
        } catch (error) {
            console.error('Error checking pending reviews:', error);
        }
    }

    displayPendingReviews(requests) {
        const container = document.getElementById('pending-reviews-container');
        if (!container || requests.length === 0) return;

        container.innerHTML = `
            <h3>Pending Reviews</h3>
            <div class="pending-reviews-list">
                ${requests.map(req => `
                    <div class="pending-review-item">
                        <p>Rate your experience with <strong>${req.other_user_name}</strong></p>
                        <button class="btn btn-primary rate-user-btn" 
                                data-request-id="${req.id}"
                                data-user-id="${req.other_user_id}"
                                data-review-type="${req.review_type}">
                            <i class="fas fa-star"></i> Leave Review
                        </button>
                    </div>
                `).join('')}
            </div>
        `;
    }

    closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) modal.style.display = 'none';
    }

    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', { 
            month: 'long', 
            day: 'numeric', 
            year: 'numeric' 
        });
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
            <span>${message}</span>
        `;

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.classList.add('fade-out');
            setTimeout(() => notification.remove(), 300);
        }, 5000);
    }
}

// Initialize rating system
let ratingSystem;
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        ratingSystem = new RatingSystem();
    });
} else {
    ratingSystem = new RatingSystem();
}

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = RatingSystem;
}
