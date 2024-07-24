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
 * GESTION DES LIKES * 
 */



