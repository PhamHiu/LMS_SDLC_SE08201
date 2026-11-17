Add-Type -AssemblyName System.Drawing

function Create-BtecMascot {
    param([string]$outputPath)
    
    $width = 512
    $height = 512
    $bmp = New-Object System.Drawing.Bitmap($width, $height, [System.Drawing.Imaging.PixelFormat]::Format32bppArgb)
    $g = [System.Drawing.Graphics]::FromImage($bmp)
    
    $g.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::AntiAlias
    $g.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic
    $g.PixelOffsetMode = [System.Drawing.Drawing2D.PixelOffsetMode]::HighQuality
    $g.TextRenderingHint = [System.Drawing.Text.TextRenderingHint]::ClearTypeGridFit

    # Transparent / Dark Cyber background circle
    $bgBrush = New-Object System.Drawing.Drawing2D.LinearGradientBrush(
        (New-Object System.Drawing.Point(0, 0)),
        (New-Object System.Drawing.Point(512, 512)),
        [System.Drawing.Color]::FromArgb(255, 3, 4, 7),
        [System.Drawing.Color]::FromArgb(255, 12, 24, 52)
    )
    $g.FillEllipse($bgBrush, 8, 8, 496, 496)
    
    # Outer Glow Ring (Cyber Blue & Lime)
    $glowPen = New-Object System.Drawing.Pen([System.Drawing.Color]::FromArgb(180, 21, 88, 201), 12)
    $g.DrawEllipse($glowPen, 14, 14, 484, 484)
    $limePen = New-Object System.Drawing.Pen([System.Drawing.Color]::FromArgb(220, 212, 255, 36), 4)
    $g.DrawEllipse($limePen, 18, 18, 476, 476)

    # Ambient light rays
    $softBrush = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::FromArgb(40, 21, 88, 201))
    $g.FillEllipse($softBrush, 100, 80, 312, 312)

    # Robot Antenna
    $antennaPen = New-Object System.Drawing.Pen([System.Drawing.Color]::FromArgb(255, 212, 255, 36), 8)
    $antennaPen.StartCap = [System.Drawing.Drawing2D.LineCap]::Round
    $antennaPen.EndCap = [System.Drawing.Drawing2D.LineCap]::Round
    $g.DrawLine($antennaPen, 256, 120, 256, 68)
    
    # Antenna Glowing Orb (Electric Lime)
    $orbGlow = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::FromArgb(120, 212, 255, 36))
    $g.FillEllipse($orbGlow, 232, 44, 48, 48)
    $orbCore = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::FromArgb(255, 212, 255, 36))
    $g.FillEllipse($orbCore, 240, 52, 32, 32)
    $orbHighlight = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::White)
    $g.FillEllipse($orbHighlight, 246, 56, 10, 10)

    # Robot Ears / Headphone Rings (Left & Right)
    $earBrush = New-Object System.Drawing.Drawing2D.LinearGradientBrush(
        (New-Object System.Drawing.Point(80, 220)),
        (New-Object System.Drawing.Point(140, 280)),
        [System.Drawing.Color]::FromArgb(255, 21, 88, 201),
        [System.Drawing.Color]::FromArgb(255, 10, 30, 70)
    )
    # Left Ear
    $g.FillEllipse($earBrush, 75, 220, 55, 90)
    $g.DrawEllipse($limePen, 75, 220, 55, 90)
    $earCore = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::FromArgb(255, 212, 255, 36))
    $g.FillEllipse($earCore, 92, 250, 20, 30)

    # Right Ear
    $earBrushR = New-Object System.Drawing.Drawing2D.LinearGradientBrush(
        (New-Object System.Drawing.Point(370, 220)),
        (New-Object System.Drawing.Point(440, 280)),
        [System.Drawing.Color]::FromArgb(255, 21, 88, 201),
        [System.Drawing.Color]::FromArgb(255, 10, 30, 70)
    )
    $g.FillEllipse($earBrushR, 382, 220, 55, 90)
    $g.DrawEllipse($limePen, 382, 220, 55, 90)
    $g.FillEllipse($earCore, 400, 250, 20, 30)

    # Robot Head Shape (Chibi rounded squircle)
    $headPath = New-Object System.Drawing.Drawing2D.GraphicsPath
    $hr = New-Object System.Drawing.Rectangle(105, 110, 302, 240)
    $radius = 70
    $headPath.AddArc($hr.X, $hr.Y, $radius, $radius, 180, 90)
    $headPath.AddArc($hr.Right - $radius, $hr.Y, $radius, $radius, 270, 90)
    $headPath.AddArc($hr.Right - $radius, $hr.Bottom - $radius, $radius, $radius, 0, 90)
    $headPath.AddArc($hr.X, $hr.Bottom - $radius, $radius, $radius, 90, 90)
    $headPath.CloseFigure()

    $headBrush = New-Object System.Drawing.Drawing2D.LinearGradientBrush(
        (New-Object System.Drawing.Point(105, 110)),
        (New-Object System.Drawing.Point(407, 350)),
        [System.Drawing.Color]::FromArgb(255, 25, 75, 175),
        [System.Drawing.Color]::FromArgb(255, 10, 32, 85)
    )
    $g.FillPath($headBrush, $headPath)
    $headBorder = New-Object System.Drawing.Pen([System.Drawing.Color]::FromArgb(255, 80, 160, 255), 6)
    $g.DrawPath($headBorder, $headPath)

    # Robot Visor / Screen (Dark glass display inside head)
    $screenPath = New-Object System.Drawing.Drawing2D.GraphicsPath
    $sr = New-Object System.Drawing.Rectangle(130, 145, 252, 165)
    $srad = 45
    $screenPath.AddArc($sr.X, $sr.Y, $srad, $srad, 180, 90)
    $screenPath.AddArc($sr.Right - $srad, $sr.Y, $srad, $srad, 270, 90)
    $screenPath.AddArc($sr.Right - $srad, $sr.Bottom - $srad, $srad, $srad, 0, 90)
    $screenPath.AddArc($sr.X, $sr.Bottom - $srad, $srad, $srad, 90, 90)
    $screenPath.CloseFigure()

    $screenBrush = New-Object System.Drawing.Drawing2D.LinearGradientBrush(
        (New-Object System.Drawing.Point(130, 145)),
        (New-Object System.Drawing.Point(130, 310)),
        [System.Drawing.Color]::FromArgb(255, 3, 7, 16),
        [System.Drawing.Color]::FromArgb(255, 12, 18, 35)
    )
    $g.FillPath($screenBrush, $screenPath)
    $screenBorder = New-Object System.Drawing.Pen([System.Drawing.Color]::FromArgb(180, 21, 88, 201), 3)
    $g.DrawPath($screenBorder, $screenPath)

    # Screen Glass Glare / Gloss
    $glarePath = New-Object System.Drawing.Drawing2D.GraphicsPath
    $glarePath.AddEllipse(145, 150, 170, 60)
    $glareBrush = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::FromArgb(35, 255, 255, 255))
    $g.FillPath($glareBrush, $glarePath)

    # Big Anime Digital Eyes (Electric Lime & Cyan, Glowing)
    # Left Eye Outer Glow
    $eyeGlow = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::FromArgb(80, 212, 255, 36))
    $g.FillEllipse($eyeGlow, 160, 180, 64, 76)
    # Left Eye Core
    $eyeCore = New-Object System.Drawing.Drawing2D.LinearGradientBrush(
        (New-Object System.Drawing.Point(165, 185)),
        (New-Object System.Drawing.Point(165, 255)),
        [System.Drawing.Color]::FromArgb(255, 220, 255, 80),
        [System.Drawing.Color]::FromArgb(255, 170, 240, 20)
    )
    $g.FillEllipse($eyeCore, 166, 186, 52, 64)

    # Right Eye Outer Glow
    $g.FillEllipse($eyeGlow, 288, 180, 64, 76)
    # Right Eye Core
    $eyeCoreR = New-Object System.Drawing.Drawing2D.LinearGradientBrush(
        (New-Object System.Drawing.Point(293, 185)),
        (New-Object System.Drawing.Point(293, 255)),
        [System.Drawing.Color]::FromArgb(255, 220, 255, 80),
        [System.Drawing.Color]::FromArgb(255, 170, 240, 20)
    )
    $g.FillEllipse($eyeCoreR, 294, 186, 52, 64)

    # Pupil & Cute Anime Highlights
    $pupilBrush = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::FromArgb(255, 10, 40, 15))
    $g.FillEllipse($pupilBrush, 178, 200, 28, 38)
    $g.FillEllipse($pupilBrush, 306, 200, 28, 38)

    # Highlights (Sparkles)
    $sparkleBig = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::White)
    $g.FillEllipse($sparkleBig, 172, 192, 18, 18)
    $g.FillEllipse($sparkleBig, 300, 192, 18, 18)
    $sparkleSmall = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::FromArgb(220, 255, 255, 255))
    $g.FillEllipse($sparkleSmall, 192, 224, 10, 10)
    $g.FillEllipse($sparkleSmall, 320, 224, 10, 10)

    # Rosy Cheeks (Blushing Chibi Dots)
    $blushBrush = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::FromArgb(140, 255, 120, 160))
    $g.FillEllipse($blushBrush, 148, 254, 28, 16)
    $g.FillEllipse($blushBrush, 336, 254, 28, 16)

    # Super Cute Smiling Digital Mouth
    $mouthPen = New-Object System.Drawing.Pen([System.Drawing.Color]::FromArgb(255, 212, 255, 36), 6)
    $mouthPen.StartCap = [System.Drawing.Drawing2D.LineCap]::Round
    $mouthPen.EndCap = [System.Drawing.Drawing2D.LineCap]::Round
    $g.DrawArc($mouthPen, 230, 240, 52, 42, 20, 140)

    # Cute Little Body / Shoulders
    $bodyBrush = New-Object System.Drawing.Drawing2D.LinearGradientBrush(
        (New-Object System.Drawing.Point(180, 360)),
        (New-Object System.Drawing.Point(332, 470)),
        [System.Drawing.Color]::FromArgb(255, 21, 88, 201),
        [System.Drawing.Color]::FromArgb(255, 8, 24, 60)
    )
    $bodyPath = New-Object System.Drawing.Drawing2D.GraphicsPath
    $bodyPath.AddArc(165, 360, 50, 50, 180, 90)
    $bodyPath.AddLine(215, 360, 297, 360)
    $bodyPath.AddArc(297, 360, 50, 50, 270, 90)
    $bodyPath.AddLine(347, 410, 360, 480)
    $bodyPath.AddLine(360, 480, 152, 480)
    $bodyPath.CloseFigure()
    $g.FillPath($bodyBrush, $bodyPath)
    $g.DrawPath($headBorder, $bodyPath)

    # Cute Glowing Heart / Tech Core on Chest
    $heartBrush = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::FromArgb(255, 212, 255, 36))
    $heartPts = @(
        (New-Object System.Drawing.Point(256, 420)),
        (New-Object System.Drawing.Point(272, 400)),
        (New-Object System.Drawing.Point(284, 412)),
        (New-Object System.Drawing.Point(256, 442)),
        (New-Object System.Drawing.Point(228, 412)),
        (New-Object System.Drawing.Point(240, 400))
    )
    $g.FillPolygon($heartBrush, $heartPts)

    # Tiny Sparkles around
    $starBrush = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::FromArgb(255, 212, 255, 36))
    $g.FillEllipse($starBrush, 120, 90, 8, 8)
    $g.FillEllipse($starBrush, 385, 95, 12, 12)
    $g.FillEllipse($starBrush, 430, 180, 7, 7)

    $bmp.Save($outputPath, [System.Drawing.Imaging.ImageFormat]::Png)
    $g.Dispose()
    $bmp.Dispose()
    Write-Output "BTEC Mascot saved to $outputPath"
}

function Create-MrBietTuot {
    param([string]$outputPath)
    
    $width = 512
    $height = 512
    $bmp = New-Object System.Drawing.Bitmap($width, $height, [System.Drawing.Imaging.PixelFormat]::Format32bppArgb)
    $g = [System.Drawing.Graphics]::FromImage($bmp)
    
    $g.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::AntiAlias
    $g.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic
    $g.PixelOffsetMode = [System.Drawing.Drawing2D.PixelOffsetMode]::HighQuality
    $g.TextRenderingHint = [System.Drawing.Text.TextRenderingHint]::ClearTypeGridFit

    # Rich Cyber Dark / Deep Blue background
    $bgBrush = New-Object System.Drawing.Drawing2D.LinearGradientBrush(
        (New-Object System.Drawing.Point(0, 0)),
        (New-Object System.Drawing.Point(512, 512)),
        [System.Drawing.Color]::FromArgb(255, 5, 8, 16),
        [System.Drawing.Color]::FromArgb(255, 18, 36, 78)
    )
    $g.FillEllipse($bgBrush, 8, 8, 496, 496)

    # Outer Neon Golden & Electric Lime Rings
    $goldPen = New-Object System.Drawing.Pen([System.Drawing.Color]::FromArgb(180, 245, 158, 11), 10)
    $g.DrawEllipse($goldPen, 14, 14, 484, 484)
    $limePen = New-Object System.Drawing.Pen([System.Drawing.Color]::FromArgb(240, 212, 255, 36), 4)
    $g.DrawEllipse($limePen, 18, 18, 476, 476)

    # Ambient warm glow behind head
    $glowWarm = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::FromArgb(45, 245, 158, 11))
    $g.FillEllipse($glowWarm, 90, 70, 332, 332)

    # Body / Professor Cloak
    $cloakBrush = New-Object System.Drawing.Drawing2D.LinearGradientBrush(
        (New-Object System.Drawing.Point(140, 340)),
        (New-Object System.Drawing.Point(372, 480)),
        [System.Drawing.Color]::FromArgb(255, 21, 88, 201),
        [System.Drawing.Color]::FromArgb(255, 8, 20, 50)
    )
    $cloakPath = New-Object System.Drawing.Drawing2D.GraphicsPath
    $cloakPath.AddArc(140, 340, 70, 70, 180, 90)
    $cloakPath.AddLine(210, 340, 302, 340)
    $cloakPath.AddArc(302, 340, 70, 70, 270, 90)
    $cloakPath.AddLine(372, 410, 395, 480)
    $cloakPath.AddLine(395, 480, 117, 480)
    $cloakPath.CloseFigure()
    $g.FillPath($cloakBrush, $cloakPath)
    $cloakBorder = New-Object System.Drawing.Pen([System.Drawing.Color]::FromArgb(255, 212, 255, 36), 4)
    $g.DrawPath($cloakBorder, $cloakPath)

    # Cute Red / Gold Bowtie
    $bowBrush = New-Object System.Drawing.Drawing2D.LinearGradientBrush(
        (New-Object System.Drawing.Point(220, 335)),
        (New-Object System.Drawing.Point(292, 375)),
        [System.Drawing.Color]::FromArgb(255, 239, 68, 68),
        [System.Drawing.Color]::FromArgb(255, 185, 28, 28)
    )
    $bowLeft = @(
        (New-Object System.Drawing.Point(256, 355)),
        (New-Object System.Drawing.Point(224, 338)),
        (New-Object System.Drawing.Point(224, 372))
    )
    $bowRight = @(
        (New-Object System.Drawing.Point(256, 355)),
        (New-Object System.Drawing.Point(288, 338)),
        (New-Object System.Drawing.Point(288, 372))
    )
    $g.FillPolygon($bowBrush, $bowLeft)
    $g.FillPolygon($bowBrush, $bowRight)
    $bowKnot = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::FromArgb(255, 245, 158, 11))
    $g.FillEllipse($bowKnot, 247, 346, 18, 18)

    # Chubby Cute Head (Peachy Warm Tone)
    $headBrush = New-Object System.Drawing.Drawing2D.LinearGradientBrush(
        (New-Object System.Drawing.Point(120, 130)),
        (New-Object System.Drawing.Point(392, 350)),
        [System.Drawing.Color]::FromArgb(255, 255, 224, 185),
        [System.Drawing.Color]::FromArgb(255, 255, 195, 145)
    )
    $g.FillEllipse($headBrush, 132, 138, 248, 222)
    $faceBorder = New-Object System.Drawing.Pen([System.Drawing.Color]::FromArgb(255, 230, 160, 110), 3)
    $g.DrawEllipse($faceBorder, 132, 138, 248, 222)

    # Fluffy Cute Professor Side-hair / Ears (Funny & Whimsical)
    $hairBrush = New-Object System.Drawing.Drawing2D.LinearGradientBrush(
        (New-Object System.Drawing.Point(100, 180)),
        (New-Object System.Drawing.Point(140, 260)),
        [System.Drawing.Color]::White,
        [System.Drawing.Color]::FromArgb(255, 225, 230, 240)
    )
    # Left hair puff
    $g.FillEllipse($hairBrush, 110, 195, 45, 65)
    $g.FillEllipse($hairBrush, 105, 235, 40, 50)
    # Right hair puff
    $g.FillEllipse($hairBrush, 357, 195, 45, 65)
    $g.FillEllipse($hairBrush, 367, 235, 40, 50)

    # Big Chubby Cheeks (Kawaii Blush)
    $blushBrush = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::FromArgb(120, 255, 110, 130))
    $g.FillEllipse($blushBrush, 152, 260, 48, 28)
    $g.FillEllipse($blushBrush, 312, 260, 48, 28)

    # Eyes & Eyebrows (Smart, expressive & playful!)
    # Eyebrows (Bushy cute white)
    $browPen = New-Object System.Drawing.Pen([System.Drawing.Color]::White, 7)
    $browPen.StartCap = [System.Drawing.Drawing2D.LineCap]::Round
    $browPen.EndCap = [System.Drawing.Drawing2D.LineCap]::Round
    $g.DrawArc($browPen, 172, 175, 48, 24, 190, 150)
    $g.DrawArc($browPen, 292, 175, 48, 24, 200, 150)

    # Left Eye (Big curious sparkling anime eye)
    $eyeWhite = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::White)
    $g.FillEllipse($eyeWhite, 172, 198, 48, 54)
    $irisBrush = New-Object System.Drawing.Drawing2D.LinearGradientBrush(
        (New-Object System.Drawing.Point(176, 202)),
        (New-Object System.Drawing.Point(216, 252)),
        [System.Drawing.Color]::FromArgb(255, 21, 88, 201),
        [System.Drawing.Color]::FromArgb(255, 5, 20, 60)
    )
    $g.FillEllipse($irisBrush, 178, 204, 38, 44)
    # Right Eye (Playful Wink or Wide Sparkle)
    $g.FillEllipse($eyeWhite, 292, 198, 48, 54)
    $irisBrushR = New-Object System.Drawing.Drawing2D.LinearGradientBrush(
        (New-Object System.Drawing.Point(296, 202)),
        (New-Object System.Drawing.Point(336, 252)),
        [System.Drawing.Color]::FromArgb(255, 21, 88, 201),
        [System.Drawing.Color]::FromArgb(255, 5, 20, 60)
    )
    $g.FillEllipse($irisBrushR, 296, 204, 38, 44)

    # Eye Sparkle Highlights
    $sparkle = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::White)
    $g.FillEllipse($sparkle, 184, 208, 16, 16)
    $g.FillEllipse($sparkle, 302, 208, 16, 16)
    $sparkle2 = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::FromArgb(220, 255, 255, 255))
    $g.FillEllipse($sparkle2, 200, 230, 8, 8)
    $g.FillEllipse($sparkle2, 318, 230, 8, 8)

    # Giant Oversized Professor Round Glasses (Iconic & Funny!)
    $glassesPen = New-Object System.Drawing.Pen([System.Drawing.Color]::FromArgb(255, 212, 255, 36), 6)
    $g.DrawEllipse($glassesPen, 160, 185, 74, 74)
    $g.DrawEllipse($glassesPen, 278, 185, 74, 74)
    # Glasses Bridge
    $g.DrawArc($glassesPen, 230, 210, 52, 24, 180, 180)
    # Glasses Temple Sides
    $templePen = New-Object System.Drawing.Pen([System.Drawing.Color]::FromArgb(255, 212, 255, 36), 4)
    $g.DrawLine($templePen, 160, 218, 130, 212)
    $g.DrawLine($templePen, 352, 218, 382, 212)

    # Glasses Glare Reflection
    $glassGlare = New-Object System.Drawing.Pen([System.Drawing.Color]::FromArgb(120, 255, 255, 255), 4)
    $g.DrawArc($glassGlare, 168, 192, 58, 58, 210, 70)
    $g.DrawArc($glassGlare, 286, 192, 58, 58, 210, 70)

    # Cute Chubby Nose
    $noseBrush = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::FromArgb(255, 235, 140, 110))
    $g.FillEllipse($noseBrush, 247, 250, 18, 14)

    # Happy Big Warm Smile (with tiny tongue!)
    $mouthPath = New-Object System.Drawing.Drawing2D.GraphicsPath
    $mouthPath.AddArc(230, 268, 52, 40, 0, 180)
    $mouthPath.CloseFigure()
    $mouthBrush = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::FromArgb(255, 150, 30, 45))
    $g.FillPath($mouthBrush, $mouthPath)
    # Tongue
    $tongueBrush = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::FromArgb(255, 255, 130, 150))
    $g.FillEllipse($tongueBrush, 244, 288, 24, 20)
    $lipPen = New-Object System.Drawing.Pen([System.Drawing.Color]::FromArgb(255, 120, 25, 35), 3)
    $g.DrawPath($lipPen, $mouthPath)

    # Tiny Whimsical Professor Mustache (Cute curly tips)
    $mustacheBrush = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::FromArgb(240, 255, 255, 255))
    $g.FillEllipse($mustacheBrush, 222, 266, 32, 14)
    $g.FillEllipse($mustacheBrush, 258, 266, 32, 14)

    # Professor Mortarboard Graduation Cap (Tilted playfully!)
    # Cap Base Skullcap
    $capBaseBrush = New-Object System.Drawing.Drawing2D.LinearGradientBrush(
        (New-Object System.Drawing.Point(200, 110)),
        (New-Object System.Drawing.Point(312, 160)),
        [System.Drawing.Color]::FromArgb(255, 15, 23, 42),
        [System.Drawing.Color]::FromArgb(255, 2, 6, 23)
    )
    $g.FillEllipse($capBaseBrush, 204, 112, 104, 45)

    # Cap Diamond Top
    $diamondBrush = New-Object System.Drawing.Drawing2D.LinearGradientBrush(
        (New-Object System.Drawing.Point(140, 70)),
        (New-Object System.Drawing.Point(372, 140)),
        [System.Drawing.Color]::FromArgb(255, 21, 88, 201),
        [System.Drawing.Color]::FromArgb(255, 10, 25, 65)
    )
    $capDiamond = @(
        (New-Object System.Drawing.Point(256, 75)),
        (New-Object System.Drawing.Point(372, 105)),
        (New-Object System.Drawing.Point(256, 135)),
        (New-Object System.Drawing.Point(140, 105))
    )
    $g.FillPolygon($diamondBrush, $capDiamond)
    $capPen = New-Object System.Drawing.Pen([System.Drawing.Color]::FromArgb(255, 212, 255, 36), 4)
    $g.DrawPolygon($capPen, $capDiamond)

    # Golden Cap Button & Tassel Hanging Down
    $tasselButton = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::FromArgb(255, 245, 158, 11))
    $g.FillEllipse($tasselButton, 249, 100, 14, 14)
    $tasselPen = New-Object System.Drawing.Pen([System.Drawing.Color]::FromArgb(255, 245, 158, 11), 3)
    $g.DrawLine($tasselPen, 256, 105, 335, 125)
    $g.DrawLine($tasselPen, 335, 125, 342, 165)
    # Tassel Fringes
    $tasselFringe = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::FromArgb(255, 245, 158, 11))
    $g.FillEllipse($tasselFringe, 336, 160, 14, 22)

    # Cute Magic/Smart Sparkles around Mr. Biết Tuốt
    $starYellow = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::FromArgb(255, 212, 255, 36))
    $g.FillEllipse($starYellow, 100, 120, 10, 10)
    $g.FillEllipse($starYellow, 410, 130, 14, 14)
    $g.FillEllipse($starYellow, 430, 270, 8, 8)
    $g.FillEllipse($starYellow, 85, 290, 8, 8)

    # Save image
    $bmp.Save($outputPath, [System.Drawing.Imaging.ImageFormat]::Jpeg)
    $g.Dispose()
    $bmp.Dispose()
    Write-Output "Mr Biet Tuot saved to $outputPath"
}

Create-BtecMascot -outputPath "C:\xampp\htdocs\CLONE_SDLC\LMS_SDLC_SE08201\LMS_SDLC_SE08201\public\images\btec_mascot.png"
Create-MrBietTuot -outputPath "C:\xampp\htdocs\CLONE_SDLC\LMS_SDLC_SE08201\LMS_SDLC_SE08201\public\images\mr_biet_tuot.jpg"

