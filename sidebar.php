<div class="logo-container">
    <img src="MNC_logo.png" alt="MNC Logo" class="logo">
</div>

<!-- Menu List of Employee di luar menu KPI -->
<div class="menu-section">
    <p class="menu-header">Employee Management</p>
    <ul class="menu-list">
        <li class="<?php echo ($active_menu == 'list_employee') ? 'active' : ''; ?>">
            <a href="dashboard.php"><i class="fas fa-th"></i> List of employee</a>
        </li>
    </ul>
</div>

<div class="menu-section">
    <p class="menu-header">KPI Menu</p>
    <ul class="menu-list">
        <li class="<?php echo ($active_menu == 'list_kpi') ? 'active' : ''; ?>">
            <a href="kpi_list.php"><i class="far fa-file"></i> List of KPI</a>
        </li>
        <li class="<?php echo ($active_menu == 'employees_kpi') ? 'active' : ''; ?>">
            <a href="employee_kpi.php"><i class="far fa-user"></i> Employees KPI</a>
        </li>
        <li class="<?php echo ($active_menu == 'history') ? 'active' : ''; ?>">
            <a href="history.php"><i class="far fa-comment"></i> History record</a>
        </li>
        <li class="<?php echo ($active_menu == 'configuration') ? 'active' : ''; ?>">
            <a href="configuration.php"><i class="far fa-clock"></i> Configuration</a>
        </li>
    </ul>
</div>

<div class="menu-section">
    <p class="menu-header">Training Management</p>
    <ul class="menu-list">
        <li class="<?php echo ($active_menu == 'training_list') ? 'active' : ''; ?>">
            <a href="training_list.php"><i class="far fa-file"></i> List of Training</a>
        </li>
        <li class="<?php echo ($active_menu == 'training_employees') ? 'active' : ''; ?>">
            <a href="training_employees.php"><i class="far fa-user"></i> List of Employee</a>
        </li>
    </ul>
</div>

<div class="menu-section">
    <p class="menu-header">Talent Pool Management</p>
    <ul class="menu-list">
        <li class="<?php echo ($active_menu == '9box') ? 'active' : ''; ?>">
            <a href="9box.php"><i class="fas fa-box"></i> 9 Box</a>
        </li>
        <li class="<?php echo ($active_menu == 'talent_employees') ? 'active' : ''; ?>">
            <a href="talent_employees.php"><i class="far fa-file"></i> List of Employee</a>
        </li>
    </ul>
</div>

<div class="menu-section">
    <ul class="menu-list">
        <li class="<?php echo ($active_menu == 'settings') ? 'active' : ''; ?>">
            <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
        </li>
    </ul>
</div>

<div class="logout-item">
    <a href="logout.php">
        <i class="fas fa-sign-out-alt logout-icon"></i> Logout
    </a>
</div>