<?php
	require ("engine/tfpdf.php"); 
	require ("engine/ean13.php");
	require ("db.php");

    $T_imna = $_POST['imienazwisko'];
    $T_em = $_POST['email'];
    $T_ko = $_POST['kod'];

    // Sprawdzamy instnienie kodu
	$T_check = mysql_query("SELECT * FROM `Pula_Biletow` WHERE `kod` = '$T_ko'") or die(mysql_error());
	$T_db_kod = mysql_fetch_array($T_check);

	// Walidujemy kod
	if ($T_db_kod['kod'] != $T_ko || $T_db_kod['aktywny'] == 0)
	{
    	$error[] = 'Nieprawidłowy kod promocyjny.';
	}
	else
	{
		// Tworzymy bilet w db
		$T_tybi = $T_db_kod['typ_biletu'];
		$Tb_bi = mysql_query("SELECT * FROM `Bilety` ORDER BY `Bilety`.`id` DESC") or die(mysql_error());
    	$Tb_db_data = mysql_fetch_array($Tb_bi);
    	$Tb_kokr = $Tb_data['kod_kreskowy']+1;

    	$date_time = date('d-m-Y, H:i');
    	$Tb_add = "INSERT INTO `Bilety`(`id`, `email`, `imienazwisko`, `data`, `kod_kreskowy`, `kod`, `typ_biletu`) VALUES ('','$T_em','$T_imna','$date_time', 'Tb_kokr', '$T_ko', '$T_tybi');";
    	$Tb_add.= "UPDATE `Pula_Biletow` SET `aktywny` = '0' WHERE `kod` = '$T_ko';";
    	mysql_query($Tb_add);
	}

	if(isset($error)){
	    foreach($error as $errors){
	        echo $errors;
	    }
	}

	// Tworzymy plik pdf jako bilet z kodem kreskowym
	$pdf=new tFPDF();
	$pdf=new PDF_EAN13();

	$pdf->AddPage();
	$pdf->Image("engine/logo/logomin.png");
	$pdf->Ln(5);
	$pdf->SetDrawColor(205,205,205);
	$pdf->SetLineWidth(1.1);

	$pdf->AddFont('DejaVu','','../DejaVuSansCondensed.ttf',true);

	$pdf->Cell(100,25,"",1,0,'L');
	$pdf->Cell(90,25,"",1,0,'R');
	$pdf->Ln(5);
	$pdf->SetFont('DejaVu','',8);

	$pdf->SetX($pdf->GetX() + 2);
	$pdf->Cell(100,0,"Nazwa wydarzenia:", 0, 0);
	$pdf->Cell(100,0,"Imię i Nazwisko", 0, 0);
	$pdf->Ln(5);
	$pdf->SetFont('DejaVu','',12);
	$pdf->SetX($pdf->GetX() + 2);
	$pdf->Cell(100,0,"3er Open", 0, 0);
	$pdf->Cell(100,0,$T_imna, 0, 0);

	$pdf->Ln(15);
	$pdf->Cell(100,25,"",1,0,'L');
	$pdf->Cell(90,25,"",1,0,'R');
	$pdf->SetXY($pdf->GetX() - 190,$pdf->GetY() + 5);
	$pdf->SetFont('DejaVu','',8);
	$pdf->SetX($pdf->GetX() + 2);
	$pdf->Cell(100,0,"Data i czas wydarzenia:", 0, 0);
	$pdf->Cell(100,0,"Typ Biletu", 0, 0);
	$pdf->Ln(5);
	$pdf->SetFont('DejaVu','',12);
	$pdf->SetX($pdf->GetX() + 2);
	$pdf->Cell(100,0,"15-17.07.2016", 0, 0);
	$pdf->Cell(50,0,$T_tybi,0, 0);

	$pdf->EAN13(140,88,$T_ko);

	$pdf->Ln(5);
	$pdf->SetFont('DejaVu','',12);
	$pdf->SetX($pdf->GetX() + 2);
	$pdf->Cell(100,0,"od 10:00 do 20:00", 0, 0);

	$pdf->Ln(10);
	$pdf->Cell(100,25,"",1,0,'L');
	$pdf->Cell(90,25,"",1,0,'R');
	$pdf->SetXY($pdf->GetX() - 190,$pdf->GetY() + 5);
	$pdf->SetFont('DejaVu','',8);
	$pdf->SetX($pdf->GetX() + 2);
	$pdf->MultiCell(100,0,"Lokalizacja:", 0);
	$pdf->Ln(5);
	$pdf->SetFont('DejaVu','',12);
	$pdf->SetX($pdf->GetX() + 2);
	$pdf->MultiCell(100,0,"Hala Widowiskowo-Sportowa", 0);
	$pdf->SetX($pdf->GetX() + 2);
	$pdf->MultiCell(100,10,"ul. Chorzowska 5", 0);
	$pdf->SetX($pdf->GetX() + 2);
	$pdf->MultiCell(100,0,"Gliwice", 0);

	$pdf->Output($T_ko . '.pdf', 'F');
	$B_plik = $T_ko . '.pdf';

	// Wysyłka biletu na email
	if (file_exists($B_plik)) {

		require("engine/phpmailer/class.phpmailer.php");
		require("engine/phpmailer/conn.php");

		$mail->setFrom('ticket@3er-esports.com');
		$mail->ContentType = "text/html"; 
		$mail->FromName = "3er Esports League";
		$mail->Subject = 'Bilet 3er Open';                                   
		$mail->CharSet = "utf-8";

		$msg = 'Witaj ' . $T_imna . '<br />
		Twój bilet został pomyślnie wygenerowany i przesłany w załączniku.<br />
		Widzimy się na 3er Open, do zobaczenia!<br /><br />

		www.3er-esports.com<br />
		www.fb.com/3erleague<br /><br />';

		$mail->MsgHTML($msg);
		$mail->AddAttachment($B_plik); 
		$mail->AddAddress($T_em);

		if(!$mail->send()) 
		{
		    echo 'Coś poszło nie tak :(';
		    echo 'Mailer Error: ' . $mail->ErrorInfo;
		} 
		else 
		{
			// usuwamy bilet z dysku po wyslaniu
		    unlink($B_plik);
		}
	}
?>