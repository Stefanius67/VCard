<!DOCTYPE html>
<html>
<head>
<meta charset="ISO-8859-1">
<title>vCard Importtest</title>
</head>
<body>
	<form action="ImportTest.php" target="_blank" enctype="multipart/form-data" method="post">
	<label for="vcfFile">import vCard - File:</label><br><br>
	<input type="file" id="vcfFile" name="vcfFile"><br><br>
	<select name="encoding" size="1">
		<option selected value="UTF-8">UTF-8</option>
		<option value="Windows-1252">Windows-1252</option>
	</select><br><br>
	<input type="submit" value="import vCard">
	</form>
</body>
</html>