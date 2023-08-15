<html>

<head>
    <style>
        /**
                Set the margins of the page to 0, so the footer and the header
                can be of the full height and width !
             **/
        @page {
            margin: 0cm 0cm;
        }

        /** Define now the real margins of every page in the PDF **/
        body {
            margin-top: 3cm;
            margin-left: 2cm;
            margin-right: 2cm;
            margin-bottom: 2cm;
        }

        /** Define the header rules **/
        header {
            font-family: Arial, Helvetica, sans-serif;
            text-align: center;
            position: fixed;
            top: 0cm;
            left: 2cm;
            right: 2cm;
            height: 3cm;
        }

        /** Define the footer rules **/
        footer {
            text-align: center;
            position: fixed;
            bottom: 0cm;
            left: 0cm;
            right: 0cm;
            height: 2cm;
            font-size: 10px;
        }

        .titulo-negrita {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            font-weight: bold;
            margin: auto;
        }

        .estudio {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            font-weight: bold;
            margin-top: 50px;
            margin-bottom: 20px;
        }

        .firma-medico {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            font-weight: bold;
            margin-top: 100px;
            margin-bottom: 0.5px;
        }

        .page-break {
            page-break-after: always;
        }
    </style>
</head>

<body>
    <!-- Define header and footer blocks before your content -->
    <header>
        <table style="width: 100%;">
            <tr>
                <td style="width: 40%;">
                    <img src="{{ url('logos/' . $reporteLectura->estudio->sede->logo_1) }}" width="80%" height="80%" />
                </td>
                <td style="width: 80%; text-align: center;">{{ $reporteLectura->estudio->sede->encabezado }}</td>
                <td style="width: 40%;">
                    <img src="{{ url('logos/' . $reporteLectura->estudio->sede->logo_2) }}" width="80%" height="80%" />
                </td>
            </tr>
        </table>
    </header>

    <footer>
        {{ $reporteLectura->estudio->sede->pie_pagina }}
    </footer>

    <!-- Wrap the content of your PDF inside a main tag -->
    <main>
        <div>{{ $reporteLectura->estudio->sede->nom_sede }}, {{ $reporteLectura->estudio->fec_estudio }}</div>
        <br>
        <br>
        <div class="titulo-negrita">Paciente: {{ $reporteLectura->estudio->paciente->nombres }}</div>
        <div class="titulo-negrita">Documento: {{ $reporteLectura->estudio->paciente->num_docu }}</div>
        <div class="titulo-negrita">Edad: {{ $reporteLectura->estudio->paciente->fec_naci }}</div>

        <p class="estudio">ESTUDIO: {{ $reporteLectura->nom_produc }}</p>

        <p>{{ $reporteLectura->lectura }}</p>

        <img class="firma-medico" src="{{ url('firmas_medicos/' . $reporteLectura->estudio->medico->firma) }}" width="100px" height="80px" />
        <p class="titulo-negrita">DR. {{ $reporteLectura->estudio->medico->name }}</p>
        <p class="titulo-negrita">MEDICO RADIOLOGO</p>
        <p class="titulo-negrita">RM: {{ $reporteLectura->estudio->medico->reg_med }}</p>

    </main>

    <script type="text/php">
        if ( isset($pdf) ) {
        $pdf->page_script('
            $font = $fontMetrics->get_font("Arial, Helvetica, sans-serif", "normal");
            $pdf->text(270, 730, "Pagina $PAGE_NUM de $PAGE_COUNT", $font, 10);
        ');
    }
</script>
</body>

</html>
