<?php

// Fonction pour formater les prix en Dirhams
function formatPrice($price) {
    return number_format($price, 2, ',', ' ') . ' DH';
}

// Fonction pour formater les dates en français
function formatDate($date, $format = 'd/m/Y à H:i') {
    $dateObj = new DateTime($date);
    return $dateObj->format($format);
}

// Fonction pour obtenir le nom du jour en français
function getWeekdayName($date) {
    $days = [
        'Monday' => 'Lundi',
        'Tuesday' => 'Mardi',
        'Wednesday' => 'Mercredi',
        'Thursday' => 'Jeudi',
        'Friday' => 'Vendredi',
        'Saturday' => 'Samedi',
        'Sunday' => 'Dimanche'
    ];
    
    $dateObj = new DateTime($date);
    $englishDay = $dateObj->format('l');
    return $days[$englishDay] ?? $englishDay;
}

// Fonction pour obtenir le nom du mois en français
function getMonthName($date) {
    $months = [
        '01' => 'Janvier', '02' => 'Février', '03' => 'Mars',
        '04' => 'Avril', '05' => 'Mai', '06' => 'Juin',
        '07' => 'Juillet', '08' => 'Août', '09' => 'Septembre',
        '10' => 'Octobre', '11' => 'Novembre', '12' => 'Décembre'
    ];
    
    $dateObj = new DateTime($date);
    $monthNumber = $dateObj->format('m');
    return $months[$monthNumber] ?? $monthNumber;
}

// Fonction pour tronquer le texte
function truncateText($text, $length = 100, $suffix = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    
    return substr($text, 0, $length) . $suffix;
}

// Fonction pour générer des breadcrumbs
function generateBreadcrumbs($items) {
    $html = '<nav aria-label="breadcrumb">';
    $html .= '<ol class="breadcrumb">';
    
    $lastIndex = count($items) - 1;
    
    foreach ($items as $index => $item) {
        if ($index === $lastIndex) {
            $html .= '<li class="breadcrumb-item active" aria-current="page">' . 
                     SecurityHelper::escape($item['name']) . '</li>';
        } else {
            $html .= '<li class="breadcrumb-item">';
            if (isset($item['url'])) {
                $html .= '<a href="' . SecurityHelper::escape($item['url']) . '">' . 
                         SecurityHelper::escape($item['name']) . '</a>';
            } else {
                $html .= SecurityHelper::escape($item['name']);
            }
            $html .= '</li>';
        }
    }
    
    $html .= '</ol>';
    $html .= '</nav>';
    
    return $html;
}

// Fonction pour générer la pagination
function generatePagination($currentPage, $totalPages, $baseUrl, $queryParams = []) {
    if ($totalPages <= 1) {
        return '';
    }
    
    $html = '<nav aria-label="Pagination">';
    $html .= '<ul class="pagination justify-content-center">';
    
    // Bouton Précédent
    if ($currentPage > 1) {
        $prevUrl = $baseUrl . '?' . http_build_query(array_merge($queryParams, ['page' => $currentPage - 1]));
        $html .= '<li class="page-item">';
        $html .= '<a class="page-link" href="' . $prevUrl . '">&laquo; Précédent</a>';
        $html .= '</li>';
    } else {
        $html .= '<li class="page-item disabled">';
        $html .= '<span class="page-link">&laquo; Précédent</span>';
        $html .= '</li>';
    }
    
    // Numéros de page
    $start = max(1, $currentPage - 2);
    $end = min($totalPages, $currentPage + 2);
    
    if ($start > 1) {
        $firstUrl = $baseUrl . '?' . http_build_query(array_merge($queryParams, ['page' => 1]));
        $html .= '<li class="page-item">';
        $html .= '<a class="page-link" href="' . $firstUrl . '">1</a>';
        $html .= '</li>';
        
        if ($start > 2) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }
    
    for ($i = $start; $i <= $end; $i++) {
        if ($i === $currentPage) {
            $html .= '<li class="page-item active">';
            $html .= '<span class="page-link">' . $i . '</span>';
            $html .= '</li>';
        } else {
            $pageUrl = $baseUrl . '?' . http_build_query(array_merge($queryParams, ['page' => $i]));
            $html .= '<li class="page-item">';
            $html .= '<a class="page-link" href="' . $pageUrl . '">' . $i . '</a>';
            $html .= '</li>';
        }
    }
    
    if ($end < $totalPages) {
        if ($end < $totalPages - 1) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
        
        $lastUrl = $baseUrl . '?' . http_build_query(array_merge($queryParams, ['page' => $totalPages]));
        $html .= '<li class="page-item">';
        $html .= '<a class="page-link" href="' . $lastUrl . '">' . $totalPages . '</a>';
        $html .= '</li>';
    }
    
    // Bouton Suivant
    if ($currentPage < $totalPages) {
        $nextUrl = $baseUrl . '?' . http_build_query(array_merge($queryParams, ['page' => $currentPage + 1]));
        $html .= '<li class="page-item">';
        $html .= '<a class="page-link" href="' . $nextUrl . '">Suivant &raquo;</a>';
        $html .= '</li>';
    } else {
        $html .= '<li class="page-item disabled">';
        $html .= '<span class="page-link">Suivant &raquo;</span>';
        $html .= '</li>';
    }
    
    $html .= '</ul>';
    $html .= '</nav>';
    
    return $html;
}

// Fonction pour afficher les messages flash
function displayFlashMessages() {
    $messages = SessionManager::get('flash_messages', []);
    if (empty($messages)) {
        return '';
    }
    
    $html = '';
    foreach ($messages as $type => $messageList) {
        foreach ($messageList as $message) {
            $alertClass = match($type) {
                'success' => 'alert-success',
                'error' => 'alert-danger',
                'warning' => 'alert-warning',
                'info' => 'alert-info',
                default => 'alert-info'
            };
            
            $html .= '<div class="alert ' . $alertClass . ' alert-dismissible fade show" role="alert">';
            $html .= SecurityHelper::escape($message);
            $html .= '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
            $html .= '</div>';
        }
    }
    
    // Effacer les messages après affichage
    SessionManager::remove('flash_messages');
    
    return $html;
}

// Fonction pour ajouter un message flash
function addFlashMessage($type, $message) {
    $messages = SessionManager::get('flash_messages', []);
    $messages[$type][] = $message;
    SessionManager::set('flash_messages', $messages);
}

// Fonction pour obtenir l'URL absolue
function getAbsoluteUrl($path = '') {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    return $protocol . '://' . $host . BASE_URL . ltrim($path, '/');
}

// Fonction pour optimiser les images
function generateResponsiveImageSrc($imagePath, $sizes = []) {
    if (empty($sizes)) {
        return $imagePath;
    }
    
    $srcset = [];
    foreach ($sizes as $width) {
        $srcset[] = $imagePath . '?w=' . $width . ' ' . $width . 'w';
    }
    
    return implode(', ', $srcset);
}

// Fonction pour générer des méta-tags
function generateMetaTags($title, $description = '', $image = '', $url = '') {
    $html = '<title>' . SecurityHelper::escape($title) . '</title>';
    
    if ($description) {
        $html .= '<meta name="description" content="' . SecurityHelper::escape($description) . '">';
        $html .= '<meta property="og:description" content="' . SecurityHelper::escape($description) . '">';
    }
    
    $html .= '<meta property="og:title" content="' . SecurityHelper::escape($title) . '">';
    $html .= '<meta property="og:type" content="website">';
    
    if ($image) {
        $html .= '<meta property="og:image" content="' . SecurityHelper::escape($image) . '">';
    }
    
    if ($url) {
        $html .= '<meta property="og:url" content="' . SecurityHelper::escape($url) . '">';
    }
    
    return $html;
}

// Fonction pour vérifier les permissions sur les fichiers
function checkFilePermissions($filePath) {
    if (!file_exists($filePath)) {
        return ['exists' => false, 'readable' => false, 'writable' => false];
    }
    
    return [
        'exists' => true,
        'readable' => is_readable($filePath),
        'writable' => is_writable($filePath),
        'size' => filesize($filePath)
    ];
}
?>