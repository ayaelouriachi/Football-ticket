function handleImageError(element) {
    if (element.dataset.type === 'team') {
        element.src = 'assets/images/default-team.png';
    }
} 