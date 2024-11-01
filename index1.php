<?php
// Conectar a la base de datos
$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "NorthWind";

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Establecer conjunto de caracteres a UTF-8
mysqli_set_charset($conn, "utf8");

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Variables del cliente y fechas (pueden ser dinámicas)
$customerID = 'ALFKI';
$fechaInicio = '1994-01-01';
$fechaFinal = '2023-12-31';

// Usar prepared statements para evitar inyección SQL
$clienteQuery = $conn->prepare("SELECT CompanyName, ContactName, ContactTitle, Address, City, Country, PostalCode 
                                FROM customers WHERE CustomerID = ?");
$clienteQuery->bind_param('s', $customerID);
$clienteQuery->execute();
$clienteResult = $clienteQuery->get_result();
$cliente = $clienteResult->fetch_assoc();

$facturasQuery = $conn->prepare("SELECT o.OrderID, o.OrderDate, o.RequiredDate, o.ShippedDate, e.FirstName, e.LastName 
                                 FROM orders o 
                                 JOIN employees e ON o.EmployeeID = e.EmployeeID 
                                 WHERE o.CustomerID = ? 
                                 AND o.OrderDate BETWEEN ? AND ?");
$facturasQuery->bind_param('sss', $customerID, $fechaInicio, $fechaFinal);
$facturasQuery->execute();
$facturasResult = $facturasQuery->get_result();
?>
<?php

// Función para convertir a ISO-8859-1
function convertirTexto($texto) {
    return iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $texto);
}
require('fpdf/fpdf.php');

class PDF extends FPDF {
    function Header() {
        global $cliente, $fechaInicio, $fechaFinal;
        // Título
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 10, 'Reporte de Facturas', 0, 1, 'C');
        // Datos del cliente
        $this->SetFont('Arial', '', 10);
        $this->Cell(0, 10, convertirTexto('Cliente: ' . $cliente['CompanyName']), 0, 1);
        $this->Cell(0, 10, convertirTexto('Contacto: ' . $cliente['ContactTitle'] . ' ' . $cliente['ContactName']), 0, 1);
        $this->Cell(0, 10, convertirTexto('Ubicación: ' . $cliente['Country'] . ', ' . $cliente['City'] . ', ' . $cliente['PostalCode']), 0, 1);
        $this->Cell(0, 10, convertirTexto('Rango de Fechas: ' . date('d/M/Y', strtotime($fechaInicio)) . ' - ' . date('d/M/Y', strtotime($fechaFinal))), 0, 1);
        $this->Ln(10); // Espacio
        
    }

    function Footer() {
        // Pie de página
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Pagina ' . $this->PageNo(), 0, 0, 'C');
    }
}

$pdf = new PDF();
$pdf->AddPage();

$totalCompras = 0;

// Iterar sobre cada factura
while ($factura = $facturasResult->fetch_assoc()) {
    // Información de la factura
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(0, 10, 'Factura #: ' . $factura['OrderID'], 0, 1);
    $pdf->Cell(0, 10, convertirTexto('Fecha de Facturación: ' . date('d/M/Y', strtotime($factura['OrderDate']))), 0, 1);
    $pdf->Cell(0, 10, convertirTexto('Empleado: ' . $factura['FirstName'] . ' ' . $factura['LastName']), 0, 1);
    $pdf->Cell(0, 10, convertirTexto('Requerida: ' . date('d/M/Y', strtotime($factura['RequiredDate']))), 0, 1);
    $pdf->Cell(0, 10, convertirTexto('Despachada: ' . date('d/M/Y', strtotime($factura['ShippedDate']))), 0, 1);
    
    $pdf->Ln(5);

    // Detalles de los productos en la factura
    $productosQuery = $conn->prepare("SELECT p.ProductName, od.Quantity, od.UnitPrice, od.Discount, 
                                      (od.Quantity * od.UnitPrice * (1 - od.Discount / 100)) AS Total 
                                      FROM order_details od 
                                      JOIN products p ON od.ProductID = p.ProductID 
                                      WHERE od.OrderID = ?");
    $productosQuery->bind_param('i', $factura['OrderID']);
    $productosQuery->execute();
    $productosResult = $productosQuery->get_result();

    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(40, 10, 'Codigo', 1);
    $pdf->Cell(60, 10, 'Nombre', 1);
    $pdf->Cell(20, 10, 'Cantidad', 1);
    $pdf->Cell(30, 10, 'Precio Uni', 1);
    $pdf->Cell(20, 10, 'Descuento', 1);
    $pdf->Cell(30, 10, 'Total', 1);
    $pdf->Ln();

    $subtotal = 0;

    while ($producto = $productosResult->fetch_assoc()) {
        $pdf->Cell(40, 10, '', 1); 
        $pdf->Cell(60, 10, convertirTexto($producto['ProductName']), 1);
        $pdf->Cell(20, 10, convertirTexto($producto['Quantity']), 1);
        $pdf->Cell(30, 10, convertirTexto(number_format($producto['UnitPrice'], 2)), 1);
        $pdf->Cell(20, 10, convertirTexto($producto['Discount'] . '%'), 1);
        $pdf->Cell(30, 10, convertirTexto(number_format($producto['Total'], 2)), 1);
        $pdf->Ln();
        
        $subtotal += $producto['Total'];
        
    }

    // Imprimir total de la factura
    $pdf->Cell(170, 10, 'Total: ' . number_format($subtotal, 2), 1, 1, 'R');
    $pdf->Ln(10); // Espacio antes de la próxima factura
    $totalCompras += $subtotal;
}

// Pie final
$pdf->Cell(0, 10, '==============================================================================', 0, 1, 'C');
$pdf->Cell(0, 10, 'Total de Compras Acumuladas: ' . number_format($totalCompras, 2), 0, 1, 'R');

$pdf->Output();

// Cerrar conexión a la base de datos
$conn->close();
?>
