<?php
require('fpdf/fpdf.php');

// Conectar a la base de datos
$conexion = new mysqli("localhost", "root", "root", "sakila");
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Función para convertir a ISO-8859-1
function convertirTexto($texto) {
    return iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $texto);
}

// Consulta SQL
$sql = "SELECT 
            store.store_id,
            category.name AS category_name,
            film.film_id,
            film.title,
            COUNT(inventory.inventory_id) AS stock,
            film.release_year
        FROM 
            film
        INNER JOIN 
            inventory ON film.film_id = inventory.film_id
        INNER JOIN 
            store ON inventory.store_id = store.store_id
        INNER JOIN 
            film_category ON film.film_id = film_category.film_id
        INNER JOIN 
            category ON film_category.category_id = category.category_id
        GROUP BY 
            store.store_id, category.name, film.film_id
        ORDER BY 
            store.store_id, category.name, film.title";

$resultado = $conexion->query($sql);

// Iniciar PDF
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);

// Cabecera del reporte
$pdf->Cell(190, 10, convertirTexto('Sakila Entretenimientos'), 0, 1, 'C');
$pdf->Cell(190, 10, convertirTexto('Listado de Peliculas'), 0, 1, 'C');

// Variables para controlar la impresión
$current_store = null;
$current_category = null;

while ($fila = $resultado->fetch_assoc()) {
    // Almacén
    if ($current_store != $fila['store_id']) {
        $current_store = $fila['store_id'];
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(190, 10, convertirTexto("Almacen: " . $current_store), 0, 1, 'L');
        $current_category = null; // Resetear categoría al cambiar de almacén
    }

    // Categoría
    if ($current_category != $fila['category_name']) {
        $current_category = $fila['category_name'];
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(190, 10, convertirTexto("Categoria: " . $current_category), 0, 1, 'L');
        // Encabezado de la tabla
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(20, 10, 'ID', 1);
        $pdf->Cell(100, 10, convertirTexto('Nombre'), 1);
        $pdf->Cell(30, 10, convertirTexto('Existencias'), 1);
        $pdf->Cell(30, 10, convertirTexto('Año'), 1);
        $pdf->Ln();
    }

    // Filas de películas
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(20, 10, convertirTexto($fila['film_id']), 1);
    $pdf->Cell(100, 10, convertirTexto($fila['title']), 1);
    $pdf->Cell(30, 10, convertirTexto($fila['stock']), 1);
    $pdf->Cell(30, 10, convertirTexto($fila['release_year']), 1);
    $pdf->Ln();
}

// Salida del archivo PDF
$pdf->Output();
?>
