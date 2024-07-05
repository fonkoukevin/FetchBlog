document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('search-input');
    const postsContainer = document.getElementById('posts-container');
    const postModalElement = document.getElementById('postModal');
    const postDetails = document.getElementById('postDetails');
    const postModal = new bootstrap.Modal(postModalElement);
    const commentModalElement = document.getElementById('commentModal');
    const commentSection = document.getElementById('commentSection');
    const commentContent = document.getElementById('commentContent');
    const submitComment = document.getElementById('submitComment');
    const commentModal = new bootstrap.Modal(commentModalElement);
    let currentPostId = null;

    // Fonction pour charger les détails d'un post
    const loadPostDetails = (postId) => {
        fetch(`/post/${postId}/details`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                postDetails.innerHTML = `
                    <div class="post-top">
                        <div class="dp">
                            <img src="/images/${data.user_image}" alt="">
                        </div>
                        <div class="post-info">
                            <p class="name">${data.username}</p>
                        </div>
                    </div>
                    <div class="post-content">
                        <h2>${data.title}</h2>
                        <img src="/images/${data.image}" alt="">
                        <p>${data.content}</p>
                    </div>
                `;
                postModal.show();
            })
            .catch(error => {
                console.error('Error loading post details:', error);
            });
    };

    // Fonction pour charger les commentaires d'un post
    const loadComments = (postId) => {
        fetch(`/post/${postId}/comments`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(comments => {
                commentSection.innerHTML = comments.map(comment => `
                <div class="comment">
                    <div class="comment-top">
                        <img src="/images/${comment.user_image}" alt="User Image" class="comment-user-image">
                        <p><strong>${comment.username}</strong> <small>${comment.createdAt}</small></p>
                    </div>
                    <p>${comment.content}</p>
                </div>
            `).join('');
                currentPostId = postId;
                commentModal.show();
            })
            .catch(error => {
                console.error('Error loading comments:', error);
            });
    };

    // Fonction pour mettre à jour le compteur de likes
    const updateLikeCount = (postId, increment) => {
        const postElement = document.querySelector(`.post-image[data-post-id="${postId}"]`).closest('.post');
        const likeCountElement = postElement.querySelector('.like-count');
        let currentCount = parseInt(likeCountElement.getAttribute('data-likes'), 10);
        currentCount += increment;
        likeCountElement.setAttribute('data-likes', currentCount);
        likeCountElement.textContent = `${currentCount} ${currentCount === 1 ? 'like' : 'likes'}`;

        // Update the post-meta section
        const postMetaElement = postElement.querySelector('.post-meta');
        postMetaElement.innerHTML = `
            <span class="like-count" data-likes="${currentCount}">${currentCount} ${currentCount === 1 ? 'like' : 'likes'}</span>,
            <span class="comment-count" data-comments="${postMetaElement.querySelector('.comment-count').getAttribute('data-comments')}">${postMetaElement.querySelector('.comment-count').getAttribute('data-comments')} comments</span>
        `;
    };

    // Fonction pour mettre à jour le compteur de commentaires
    const updateCommentCount = (postId, increment) => {
        const postElement = document.querySelector(`.post-image[data-post-id="${postId}"]`).closest('.post');
        const commentCountElement = postElement.querySelector('.comment-count');
        let currentCount = parseInt(commentCountElement.getAttribute('data-comments'), 10);
        currentCount += increment;
        commentCountElement.setAttribute('data-comments', currentCount);
        commentCountElement.textContent = `${currentCount} ${currentCount === 1 ? 'comment' : 'comments'}`;

        // Update the post-meta section
        const postMetaElement = postElement.querySelector('.post-meta');
        postMetaElement.innerHTML = `
            <span class="like-count" data-likes="${postMetaElement.querySelector('.like-count').getAttribute('data-likes')}">${postMetaElement.querySelector('.like-count').getAttribute('data-likes')} likes</span>,
            <span class="comment-count" data-comments="${currentCount}">${currentCount} ${currentCount === 1 ? 'comment' : 'comments'}</span>
        `;
    };

    // Délégation d'événements pour les détails des posts
    postsContainer.addEventListener('click', event => {
        if (event.target.classList.contains('post-image')) {
            const postId = event.target.getAttribute('data-post-id');
            loadPostDetails(postId);
        } else if (event.target.classList.contains('fa-comment')) {
            const postId = event.target.closest('.post').querySelector('.post-image').getAttribute('data-post-id');
            loadComments(postId);
        } else if (event.target.classList.contains('like-btn') || event.target.classList.contains('liked')) {
            const postId = event.target.getAttribute('data-post-id');
            fetch(`/post/${postId}/like`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        updateLikeCount(postId, event.target.classList.contains('liked') ? -1 : 1);
                        event.target.classList.toggle('liked');
                        event.target.classList.toggle('like-btn');
                    }
                })
                .catch(error => {
                    console.error('Error liking post:', error);
                });
        }
    });

    // Écouteur pour la recherche
    searchInput.addEventListener('input', () => {
        const query = searchInput.value.trim();

        if (query.length > 0) {
            fetch(`/post/search?q=${query}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.text();
                })
                .then(html => {
                    console.log('Search response:', html); // Log response for debugging
                    postsContainer.innerHTML = html;
                })
                .catch(error => {
                    console.error('Error during search:', error);
                });
        } else {
            // Recharger tous les posts si la recherche est vide
            fetch('/post')
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.text();
                })
                .then(html => {
                    console.log('Reload all posts response:', html); // Log response for debugging
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newPostsContainer = doc.getElementById('posts-container');
                    postsContainer.innerHTML = newPostsContainer.innerHTML;
                })
                .catch(error => {
                    console.error('Error reloading posts:', error);
                });
        }
    });

    // Réinitialiser l'état de la page lors de la fermeture du modal
    postModalElement.addEventListener('hidden.bs.modal', () => {
        postDetails.innerHTML = '';
    });

    // Gestion de la soumission des commentaires
    submitComment.addEventListener('click', () => {
        const content = commentContent.value.trim();
        if (content && currentPostId) {
            fetch(`/post/${currentPostId}/comments`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ content }),
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.error) {
                        alert(data.error);
                    } else {
                        commentContent.value = '';
                        loadComments(currentPostId);
                        updateCommentCount(currentPostId, 1);
                    }
                })
                .catch(error => {
                    console.error('Error submitting comment:', error);
                });
        } else {
            alert('Comment content cannot be empty');
        }
    });
});
