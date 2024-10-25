<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:template match="/">
        <html>
        <head>
            <title>Filmes Más Solicitados</title>
            <style>
                table {
                    border-collapse: collapse;
                    width: 100%;
                }
                th, td {
                    border: 1px solid black;
                    padding: 8px;
                    text-align: left;
                }
                th {
                    background-color: #f2f2f2;
                }
            </style>
        </head>
        <body>
            <h2>Reporte de Filmes Más Solicitados</h2>
            <table>
                <tr>
                    <th>Código</th>
                    <th>Nombre</th>
                    <th>Veces Alquilada</th>
                    <th>Total Generado</th>
                </tr>
                <xsl:for-each select="FilmesSolicitados/Filme">
                    <tr>
                        <td><xsl:value-of select="Codigo"/></td>
                        <td><xsl:value-of select="Nombre"/></td>
                        <td><xsl:value-of select="VecesAlquilada"/></td>
                        <td><xsl:value-of select="TotalGenerado"/></td>
                    </tr>
                </xsl:for-each>
                <tr>
                    <td colspan="3">Gran Total Generado</td>
                    <td><xsl:value-of select="FilmesSolicitados/GranTotalGenerado"/></td>
                </tr>
            </table>
        </body>
        </html>
    </xsl:template>
</xsl:stylesheet>


