<?php
require('fpdf/fpdf.php');

// Configuración de conexión a la base de datos
$dsn = 'mysql:host=localhost;dbname=sakila;charset=utf8';
$username = 'root'; // Cambia por tu usuario
$password = 'root'; // Cambia por tu contraseña

try {
    $pdo = new PDO($dsn, $username, $password);
} catch (PDOException $e) {
    echo 'Conexión fallida: ' . $e->getMessage();
    exit;
}

// Variables de fecha
$fechaInicio = '2005-01-01'; // Cambia la fecha inicial
$fechaFin = '2023-12-31'; // Cambia la fecha final

// Consulta
$query = "
    SELECT 
        s.store_id AS almacen,
        f.title AS nombre,
        c.name AS genero,
        SUM(p.amount) AS monto
    FROM
        payment p
    JOIN 
        rental r ON p.rental_id = r.rental_id
    JOIN 
        inventory i ON r.inventory_id = i.inventory_id
    JOIN 
        film f ON i.film_id = f.film_id
    JOIN 
        film_category fc ON f.film_id = fc.film_id
    JOIN 
        category c ON fc.category_id = c.category_id
    JOIN 
        store s ON i.store_id = s.store_id
    WHERE 
        p.payment_date BETWEEN :fecha_inicio AND :fecha_fin
    GROUP BY 
        s.store_id, f.title, c.name
    ORDER BY 
        SUM(p.amount) DESC
";

$stmt = $pdo->prepare($query);
$stmt->execute(['fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin]);
$resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Crear PDF
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);

// Título
$pdf->Cell(0, 10, 'Sakila Entretenimientos', 0, 1, 'C');
$pdf->Cell(0, 10, "Reporte de ingresos desde $fechaInicio a $fechaFin", 0, 1, 'C');
$pdf->Ln(10);

// Encabezados
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(30, 10, 'Almacén', 1);
$pdf->Cell(80, 10, 'Nombre', 1);
$pdf->Cell(40, 10, 'Género', 1);
$pdf->Cell(40, 10, 'Monto', 1);
$pdf->Ln();

// Datos
$pdf->SetFont('Arial', '', 12);
$totalAlquiler = 0;

foreach ($resultados as $row) {
    $pdf->Cell(30, 10, $row['almacen'], 1);
    $pdf->Cell(80, 10, $row['nombre'], 1);
    $pdf->Cell(40, 10, $row['genero'], 1);
    $pdf->Cell(40, 10, number_format($row['monto'], 2), 1);
    $pdf->Ln();
    $totalAlquiler += $row['monto'];
}

// Total acumulado
$pdf->Ln(10);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, "Total Alquiler Acumulado por Almacén: " . number_format($totalAlquiler, 2), 0, 1, 'R');

// Salvar el PDF
$pdf->Output('D', 'reporte_ingresos.pdf');
?>
