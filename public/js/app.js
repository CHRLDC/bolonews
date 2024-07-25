/**
 * Toggle edit profil utilisateur
 * passe par un onclick...
 */
function toggleEditMode() {
    var infoDiv = document.getElementById('profile-info');
    var editDiv = document.getElementById('edit-profile');
    var isEditing = editDiv.style.display === 'block';

    if (isEditing) {
        infoDiv.style.display = 'block';
        editDiv.style.display = 'none';
    } else {
        infoDiv.style.display = 'none';
        editDiv.style.display = 'block';
    }
}



/**
 * Rôle: Gestion des Likes cliqués *
 */

document.addEventListener('DOMContentLoaded', function () {

    // Récupérer tous les boutons Like (picto coeur), pour chacun:
    document.querySelectorAll('.picto-coeur').forEach(button => {

        // Ecouter le click utilisateur, si il clique:
        button.addEventListener('click', function () {

            // Récupérer l'ID de l'article depuis un attribut data
            let articleId = this.getAttribute('data-article-id');

            // Envoyer une requête fetch pour basculer l'état du like
            fetch(`/like/toggle/${articleId}`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json'
                }
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Basculer la classe 'liked' en fonction de la réponse du serveur
                        if (data.isLiked) {
                            this.classList.add('liked');
                        } else {
                            this.classList.remove('liked');
                        }

                        // Mettre à jour le compteur de likes
                        let likeCountElement = document.querySelector(`.like-count[data-article-id="${articleId}"]`);
                        if (likeCountElement) {
                            likeCountElement.textContent = data.likeCount;
                        }
                    }
                })
                .catch(error => console.error('Erreur:', error));
        });
    });
});




