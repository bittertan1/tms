/* Styles for Add/Edit Employee Form */
.form-container {
    background-color: #fff;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    margin-bottom: 30px;
}

.form-group {
    margin-bottom: 20px;
}

.flex-row {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
}

.form-field {
    flex: 1;
}

.form-label {
    display: block;
    margin-bottom: 5px;
    font-size: 14px;
    color: #495057;
    font-weight: 500;
}

.form-input {
    width: 100%;
    padding: 10px;
    border: 1px solid #e0e6ed;
    border-radius: 4px;
    font-size: 14px;
    box-sizing: border-box;
}

.form-select {
    width: 100%;
    padding: 10px;
    border: 1px solid #e0e6ed;
    border-radius: 4px;
    font-size: 14px;
    background-color: #fff;
    cursor: pointer;
}

.form-submit {
    text-align: center;
    margin-top: 30px;
}

.save-btn, .cancel-btn {
    padding: 10px 30px;
    border-radius: 4px;
    font-size: 14px;
    cursor: pointer;
    margin: 0 10px;
    border: none;
}

.save-btn {
    background-color: #4CAF50;
    color: white;
}

.cancel-btn {
    background-color: #f44336;
    color: white;
}

.alert {
    padding: 10px 15px;
    margin-bottom: 20px;
    border-radius: 4px;
    font-size: 14px;
}

.alert-success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-danger {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.flex-column {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

/* Dropdown custom styling */
.select-wrapper {
    position: relative;
}

.select-wrapper:after {
    content: "\f078";
    font-family: "Font Awesome 5 Free";
    font-weight: 900;
    position: absolute;
    right: 10px;
    top: 10px;
    color: #6c757d;
    pointer-events: none;
}