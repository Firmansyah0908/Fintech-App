document.addEventListener("DOMContentLoaded", function() {
    const chartContainer = document.getElementById("lineChartContainer");
    
    if (chartContainer && window.chartDataFromDB) {
        const data = window.chartDataFromDB.data;
        const months = window.chartDataFromDB.labels;
        let maxVal = window.chartDataFromDB.maxVal;
        
        if (maxVal < 1) maxVal = 1;

        if (data.length === 0) {
            chartContainer.innerHTML = "<div class='text-muted' style='width:100%; text-align:center; padding-top:50px;'>Tidak ada data</div>";
            return;
        }

        const width = chartContainer.clientWidth;
        // Kurangi height untuk teks di bawah (sumbu X)
        const height = chartContainer.clientHeight - 30; 
        
        let svgHTML = `<svg width="100%" height="100%" viewBox="0 0 ${width} ${height + 30}" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">`;
        
        svgHTML += `
        <defs>
            <linearGradient id="lineGradient" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="#10b981" stop-opacity="0.25"></stop>
                <stop offset="100%" stop-color="#10b981" stop-opacity="0"></stop>
            </linearGradient>
        </defs>`;
        
        // 1. Gambar Garis Grid (Garis Horizontal)
        // Kita beri padding atas buatan dengan cara menggeser nilai Y sedikit ke bawah.
        // Asumsikan kita "menyusutkan" tinggi efektif grafik sebesar 15px dari atas.
        const topPadding = 15; 
        const effectiveHeight = height - topPadding;

        const gridLines = [0, maxVal*0.25, maxVal*0.5, maxVal*0.75, maxVal];
        gridLines.forEach(val => {
            // Kalkulasi Y baru: 0 berada di bawah (height), maxVal berada di atas (topPadding)
            const y = height - (val / maxVal) * effectiveHeight;
            svgHTML += `<line x1="40" y1="${y}" x2="${width}" y2="${y}" stroke="#f3f4f6" stroke-width="1"/>`;
            svgHTML += `<text x="0" y="${y+4}" fill="#6b7280" font-size="10" font-family="Inter">${val.toFixed(1)} jt</text>`;
        });

        if (data.length === 1) {
            const y = height - (data[0] / maxVal) * effectiveHeight;
            const x = (width + 40) / 2;
            svgHTML += `<circle cx="${x}" cy="${y}" r="4" fill="#fff" stroke="#10b981" stroke-width="2"/>`;
            svgHTML += `<text x="${x}" y="${height + 22}" fill="#6b7280" font-size="11" font-family="Inter" text-anchor="middle">${months[0]}</text>`;
            svgHTML += `</svg>`;
            chartContainer.innerHTML = svgHTML;
            return;
        }

        let pathD = "";
        let points = [];
        
        // 2. Gambar Garis SVG
        // PERBAIKAN: Beri margin kanan 20px agar teks paling ujung tidak terpotong
        const rightMargin = 20; 
        const stepX = (width - 40 - rightMargin) / (data.length - 1);

        data.forEach((val, index) => {
            const x = 40 + (index * stepX);
            const y = height - (val / maxVal) * effectiveHeight;
            points.push({x, y});
            
            if (index === 0) {
                pathD += `M ${x} ${y} `;
            } else {
                const prevX = points[index-1].x;
                const prevY = points[index-1].y;
                
                const ctrlX1 = prevX + (stepX / 3);
                const ctrlX2 = x - (stepX / 3);
                
                pathD += `C ${ctrlX1} ${prevY}, ${ctrlX2} ${y}, ${x} ${y} `;
            }
        });

        // Path Gradient
        let fillPath = pathD + `L ${points[points.length-1].x} ${height} L ${points[0].x} ${height} Z`;
        svgHTML += `<path d="${fillPath}" fill="url(#lineGradient)" stroke="none"/>`;

        // Path Utama
        svgHTML += `<path d="${pathD}" fill="none" stroke="#10b981" stroke-width="3" stroke-linecap="round"/>`;
        
        // Titik & Label
        points.forEach((pt, index) => {
            svgHTML += `<circle cx="${pt.x}" cy="${pt.y}" r="4" fill="#fff" stroke="#10b981" stroke-width="2"/>`;
            // Cek jika ini adalah label paling awal, geser sedikit ke kanan agar tidak menabrak Y-Axis
            let textAnchor = "middle";
            let textX = pt.x;
            if (index === 0) {
                textAnchor = "start";
                textX = pt.x - 5;
            } else if (index === data.length - 1) {
                 textAnchor = "end";
                 textX = pt.x + 5;
            }

            svgHTML += `<text x="${textX}" y="${height + 22}" fill="#6b7280" font-size="11" font-family="Inter" text-anchor="${textAnchor}">${months[index]}</text>`;
        });

        svgHTML += `</svg>`;
        chartContainer.innerHTML = svgHTML;
    }
});