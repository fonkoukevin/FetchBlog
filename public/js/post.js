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
    const categoryFilter = document.getElementById('category-filter');
    // const postsContainer = document.getElementById('posts-container');
    const loadPostDetails = (postId) => {
        fetch(`/post/${postId}/details`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                const categoriesHtml = data.categories.map(category => `<span class="badge bg-secondary">${category}</span>`).join(' ');

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
                        <div class="post-categories">
                            ${categoriesHtml}
                        </div>
                    </div>
                `;
                postModal.show();
            })
            .catch(error => {
                console.error('Error loading post details:', error);
            });
    };


    categoryFilter.addEventListener('change', () => {
        const categoryId = categoryFilter.value;

        // Check if "Aucun catégorie" is selected (assuming it's an empty value)
        const url = categoryId ? `/post/filter?category_id=${categoryId}` : `/post/filter`;

        fetch(url)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.text();
            })
            .then(html => {
                postsContainer.innerHTML = html;
            })
            .catch(error => {
                console.error('Error during filter:', error);
            });
    });


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

    const updateLikeCount = (postId, newCount) => {
        const postElement = document.querySelector(`.post-image[data-post-id="${postId}"]`).closest('.post');
        const likeCountElement = postElement.querySelector('.like-count');

        likeCountElement.setAttribute('data-likes', newCount);
        likeCountElement.textContent = `${newCount} ${newCount === 1 ? 'like' : 'likes'}`;

        const postMetaElement = postElement.querySelector('.post-meta');
        postMetaElement.innerHTML = `
            <span class="like-count" data-likes="${newCount}">${newCount} ${newCount === 1 ? 'like' : 'likes'}</span>,
            <span class="comment-count" data-comments="${postMetaElement.querySelector('.comment-count').getAttribute('data-comments')}">${postMetaElement.querySelector('.comment-count').getAttribute('data-comments')} comments</span>
        `;
    };

    const updateCommentCount = (postId, increment) => {
        const postElement = document.querySelector(`.post-image[data-post-id="${postId}"]`).closest('.post');
        const commentCountElement = postElement.querySelector('.comment-count');
        let currentCount = parseInt(commentCountElement.getAttribute('data-comments'), 10);
        currentCount += increment;
        commentCountElement.setAttribute('data-comments', currentCount);
        commentCountElement.textContent = `${currentCount} ${currentCount === 1 ? 'comment' : 'comments'}`;

        const postMetaElement = postElement.querySelector('.post-meta');
        postMetaElement.innerHTML = `
            <span class="like-count" data-likes="${postMetaElement.querySelector('.like-count').getAttribute('data-likes')}">${postMetaElement.querySelector('.like-count').getAttribute('data-likes')} likes</span>,
            <span class="comment-count" data-comments="${currentCount}">${currentCount} ${currentCount === 1 ? 'comment' : 'comments'}</span>
        `;
    };

    postsContainer.addEventListener('click', event => {
        if (event.target.classList.contains('post-image')) {
            const postId = event.target.getAttribute('data-post-id');
            loadPostDetails(postId);
        } else if (event.target.classList.contains('fa-comment')) {
            const postId = event.target.closest('.post').querySelector('.post-image').getAttribute('data-post-id');
            loadComments(postId);
        } else if (event.target.classList.contains('like-btn') || event.target.classList.contains('liked')) {
            const postId = event.target.getAttribute('data-post-id');
            const likeAction = event.target.classList.contains('liked') ? 'unlike' : 'like';

            fetch(`/post/${postId}/${likeAction}`, {
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
                    if (data.message) {
                        const newLikeCount = data.likeCount;
                        updateLikeCount(postId, newLikeCount);
                        event.target.classList.toggle('liked');
                        event.target.classList.toggle('like-btn');

                        if (event.target.classList.contains('liked')) {
                            event.target.classList.remove('far');
                            event.target.classList.add('fa-solid');
                        } else {
                            event.target.classList.remove('fa-solid');
                            event.target.classList.add('far');
                        }
                    }
                })
                .catch(error => {
                    console.error('Error liking post:', error);
                });
        }
    });

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
                    postsContainer.innerHTML = html;
                })
                .catch(error => {
                    console.error('Error during search:', error);
                });
        } else {
            fetch('/post')
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.text();
                })
                .then(html => {
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

    postModalElement.addEventListener('hidden.bs.modal', () => {
        postDetails.innerHTML = '';
    });

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
        }
    });
});
