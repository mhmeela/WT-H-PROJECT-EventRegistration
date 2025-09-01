<!DOCTYPE html>
<html>
<head>
<title>Sign Up</title>
</head>
<body>
<h2>Create an Account</h2>

<form method="post" action="signup_process.php">
    <div>
        <label>Create a new username:</label>
        <input type="text" name="username" required>
    </div>
    <div>
        <label>Password:</label>
        <input type="password" name="password" required>
    </div>
    <div>
        <label for="email">E-mail:</label>
        <input type="email" id="email" name="email">
    </div>
    <div>
        <label for="phone">Phone Number:</label>
        <input type="number" id="phone" name="phone">
    </div>

    <div>
        <label for="gender">Gender:</label>
        <input type="radio" id="male" name="gender" value="male"> <label for="male">Male</label>
        <input type="radio" id="female" name="gender" value="female"> <label for="female">Female</label>
        <input type="radio" id="other" name="gender" value="other"> <label for="other">Other</label>
    </div>

   <!-- <div>
        <label for="event">Select event:</label>
        <input type="radio" id="workshop" name="event" value="workshop"> <label for="workshop">Workshop</label>
        <input type="radio" id="seminar" name="event" value="seminar"> <label for="seminar">Seminar</label>
        <input type="radio" id="cosplay" name="event" value="cosplay"> <label for="cosplay">Cosplay</label>
    </div>-->

    <div>
        <label for="blood-group">Blood Group:</label>
        <select id="blood-group" name="blood-group">
            <option value="">--Select--</option>
            <option value="A+">A+</option>
            <option value="A-">A-</option>
            <option value="B+">B+</option>
            <option value="B-">B-</option>
            <option value="O+">O+</option>
            <option value="O-">O-</option>
            <option value="AB+">AB+</option>
            <option value="AB-">AB-</option>
        </select>
    </div>

    <div>
        <label for="emergency-name">Emergency Contact Name:</label>
        <input type="text" id="emergency-name" name="emergency-name" placeholder="Enter name">
    </div>

    <div>
        <label for="emergency-number">Emergency Contact Number:</label>
        <input type="tel" id="emergency-number" name="emergency-number" placeholder="Enter phone number">
    </div>

    <div>
        <input type="submit" value="Sign Up">
    </div>
</form>

<p>Already have an account? 
    <a href="login.php">Login here</a>
</p>

</body>
</html>
