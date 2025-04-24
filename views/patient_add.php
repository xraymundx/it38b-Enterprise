<!DOCTYPE html>
<html>
<head>
    <title>Add New Patient</title>
</head>
<body>
    <h1>Add New Patient</h1>
    <form action="index.php?action=store" method="post">
        <div>
            <label for="name">Name:</label>
            <input type="text" id="name" name="name" required>
        </div>
        <div>
            <label for="birth_date">Birth Date:</label>
            <input type="date" id="birth_date" name="birth_date">
        </div>
        <div>
            <label for="medical_history">Medical History:</label><br>
            <textarea id="medical_history" name="medical_history" rows="4" cols="50"></textarea>
        </div>
        <button type="submit">Add Patient</button>
        <p><a href="index.php?action=list">Back to Patient List</a></p>
    </form>
</body>
</html>