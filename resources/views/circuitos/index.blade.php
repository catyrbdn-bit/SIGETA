<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Circuitos - SIGETA</title>



</head>
<body>
    
    <h1>Circuitos registrados</h1>

    <h3>Lista de Circuitos</h3>

    @if (session('success'))
    <p id="mensaje-exito" style="color:green;">{{ session('success') }}</p>
    <script>
        setTimeout(function() {
            document.getElementById('mensaje-exito').style.display = 'none';
        }, 3000);
    </script>
    @endif

    





</body>
</html>