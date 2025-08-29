<?php
// SROI Ex-post Analysis Main Page
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Get project data for header
$projects = getUserProjects($conn, $user_id);
$selected_project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : (count($projects) > 0 ? $projects[0]['id'] : 0);
$selected_project = $selected_project_id ? getProjectById($conn, $selected_project_id, $user_id) : null;

// Include components
include 'components/header.php';
include 'components/project-selector.php';
include 'components/input-section.php';
include 'components/output-section.php';
include 'components/footer.php';
?>