#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
GSC PDF Report Generator Engine
vicksWalkiria / victor-alonso.es
"""

import os
import sys
import csv
import zipfile
import tempfile
import shutil
import subprocess
from datetime import datetime

def log_info(msg):
    print(f"[INFO] {msg}")

def log_error(msg):
    print(f"[ERROR] {msg}", file=sys.stderr)

def clean_num(val):
    if not val:
        return 0.0
    val = val.strip().replace("%", "").replace("\u202f", "").replace("\xa0", "")
    # Si contiene comas y puntos, ej: 1.234,56 o 1,234.56
    if "," in val and "." in val:
        if val.rfind(",") > val.rfind("."):
            # Formato europeo: 1.234,56 -> 1234.56
            val = val.replace(".", "").replace(",", ".")
        else:
            # Formato americano: 1,234.56 -> 1234.56
            val = val.replace(",", "")
    elif "," in val:
        # Podría ser decimal con coma: 12,5 -> 12.5 o miles 1,234 -> 1234
        # Si la coma está seguida de exactamente 3 dígitos, asumimos que es separador de miles si el número es grande,
        # pero en GSC las posiciones y CTRs suelen tener decimales.
        # Por seguridad, si es CTR o posición (números pequeños), la coma suele ser decimal.
        # Una regla empírica simple: si hay un solo separador y está al final
        parts = val.split(",")
        if len(parts) == 2 and len(parts[1]) != 3:
            val = val.replace(",", ".")
        elif len(parts) == 2 and len(parts[1]) == 3:
            # Podría ser 1,234 (miles) o 1,234 (decimal con 3 decimales).
            # Para CTR/Posición suele ser decimal. Para Impresiones/Clics es miles.
            # Convertimos comas a puntos si asumimos decimales, pero resolveremos basándonos en la conversión directa:
            # Si el valor entero sin coma es muy alto, podría ser miles.
            # Intentamos primero con punto decimal.
            val = val.replace(",", ".")
    try:
        return float(val)
    except ValueError:
        # Quitar caracteres no numéricos residuales
        cleaned = "".join([c for c in val if c.isdigit() or c in ".-"])
        try:
            return float(cleaned)
        except ValueError:
            return 0.0

def parse_date(date_str):
    date_str = date_str.strip()
    for fmt in ("%Y-%m-%d", "%d/%m/%Y", "%Y/%m/%d", "%d-%m-%Y"):
        try:
            return datetime.strptime(date_str, fmt)
        except ValueError:
            continue
    return None

def read_csv_safely(filepath):
    # Detectar codificación
    encoding = "utf-8"
    try:
        with open(filepath, "rb") as f:
            raw = f.read(4)
            if raw.startswith(b"\xff\xfe") or raw.startswith(b"\xfe\xff"):
                encoding = "utf-16"
            elif raw.startswith(b"\xef\xbb\xbf"):
                encoding = "utf-8-sig"
    except Exception:
        pass

    try:
        with open(filepath, "r", encoding=encoding, errors="replace") as f:
            content = f.read()
    except Exception as e:
        log_error(f"Error al leer archivo {filepath}: {e}")
        return []

    # Detectar dialecto/separador
    separator = ","
    if ";" in content[:1000]:
        separator = ";"
    elif "\t" in content[:1000]:
        separator = "\t"

    lines = content.splitlines()
    reader = csv.reader(lines, delimiter=separator)
    data = []
    for row in reader:
        if row:
            data.append([col.strip() for col in row])
    return data

class GSCReportGenerator:
    def __init__(self, zip_path, output_pdf):
        self.zip_path = zip_path
        self.output_pdf = output_pdf
        self.temp_dir = None
        self.extracted_files = {}
        self.logo_exists = False
        
        # Datos procesados
        self.domain = "tuweb.com"
        self.dates_data = []
        self.queries_data = []
        self.pages_data = []
        self.devices_data = []

        # KPIs globales
        self.total_clicks = 0
        self.total_impressions = 0
        self.avg_ctr = 0.0
        self.avg_position = 0.0
        self.start_date = None
        self.end_date = None

    def execute(self):
        try:
            # 1. Crear directorio temporal
            self.temp_dir = tempfile.mkdtemp(prefix="gsc_report_")
            log_info(f"Creado directorio temporal: {self.temp_dir}")

            # Copiar logo corporativo si existe
            script_dir = os.path.dirname(os.path.abspath(__file__))
            project_root = os.path.dirname(os.path.dirname(script_dir))
            logo_src = os.path.join(project_root, "apple-touch-icon.png")
            if os.path.exists(logo_src):
                shutil.copy2(logo_src, os.path.join(self.temp_dir, "logo.png"))
                self.logo_exists = True
                log_info("Logo corporativo copiado para compilación LaTeX.")

            # 2. Extraer ZIP
            with zipfile.ZipFile(self.zip_path, 'r') as zip_ref:
                zip_ref.extractall(self.temp_dir)
            log_info("ZIP extraído correctamente.")

            # 3. Mapear archivos
            self.map_files()

            # 4. Procesar los datos
            self.process_dates()
            self.process_queries()
            self.process_pages()
            self.process_devices()

            # 5. Generar LaTeX y compilar
            self.generate_pdf()

            return True
        except Exception as e:
            log_error(f"Fallo en la ejecución del generador: {e}")
            import traceback
            traceback.print_exc()
            return False
        finally:
            if self.temp_dir and os.path.exists(self.temp_dir):
                shutil.rmtree(self.temp_dir)
                log_info("Limpieza del directorio temporal completada.")

    def map_files(self):
        # Escanear archivos en el directorio temporal
        for root, dirs, files in os.walk(self.temp_dir):
            for file in files:
                name_lower = file.lower()
                filepath = os.path.join(root, file)
                
                if "fechas" in name_lower or "dates" in name_lower or "gráfico" in name_lower or "grafico" in name_lower or "chart" in name_lower:
                    self.extracted_files["dates"] = filepath
                elif "consultas" in name_lower or "queries" in name_lower:
                    self.extracted_files["queries"] = filepath
                elif "páginas" in name_lower or "paginas" in name_lower or "pages" in name_lower:
                    self.extracted_files["pages"] = filepath
                elif "dispositivos" in name_lower or "devices" in name_lower:
                    self.extracted_files["devices"] = filepath

        log_info(f"Archivos identificados: {list(self.extracted_files.keys())}")

    def map_headers(self, headers):
        # Mapear nombres de columnas a claves genéricas
        mapping = {}
        for idx, h in enumerate(headers):
            h_clean = h.lower().strip()
            if h_clean in ("fecha", "date", "fechas", "dates"):
                mapping["date"] = idx
            elif "consulta" in h_clean or "query" in h_clean or "queries" in h_clean or "consultas" in h_clean:
                mapping["query"] = idx
            elif "página" in h_clean or "pagina" in h_clean or "page" in h_clean or "pages" in h_clean or "páginas" in h_clean:
                mapping["page"] = idx
            elif "dispositivo" in h_clean or "device" in h_clean or "devices" in h_clean or "dispositivos" in h_clean:
                mapping["device"] = idx
            elif h_clean in ("clics", "clicks", "click", "clic"):
                mapping["clicks"] = idx
            elif h_clean in ("impresiones", "impressions", "impresión", "impresion"):
                mapping["impressions"] = idx
            elif h_clean == "ctr":
                mapping["ctr"] = idx
            elif h_clean in ("posición", "posicion", "position", "positions", "posiciones"):
                mapping["position"] = idx
        return mapping

    def process_dates(self):
        if "dates" not in self.extracted_files:
            log_info("No se encontró el archivo de fechas. Saltando.")
            return

        csv_rows = read_csv_safely(self.extracted_files["dates"])
        if not csv_rows or len(csv_rows) < 2:
            return

        headers = csv_rows[0]
        col_map = self.map_headers(headers)
        
        required = ("date", "clicks", "impressions", "ctr", "position")
        if not all(k in col_map for k in required):
            log_error(f"Faltan columnas requeridas en fechas. Cabeceras: {headers}")
            return

        dates_parsed = []
        for row in csv_rows[1:]:
            if len(row) <= max(col_map.values()):
                continue
            date_val = parse_date(row[col_map["date"]])
            if not date_val:
                continue
            clicks = int(clean_num(row[col_map["clicks"]]))
            impressions = int(clean_num(row[col_map["impressions"]]))
            ctr = clean_num(row[col_map["ctr"]])
            position = clean_num(row[col_map["position"]])
            
            dates_parsed.append({
                "date": date_val,
                "clicks": clicks,
                "impressions": impressions,
                "ctr": ctr,
                "position": position
            })

        # Ordenar por fecha asc
        dates_parsed.sort(key=lambda x: x["date"])
        self.dates_data = dates_parsed

        if dates_parsed:
            self.start_date = dates_parsed[0]["date"]
            self.end_date = dates_parsed[-1]["date"]
            
            # Calcular KPIs globales
            self.total_clicks = sum(d["clicks"] for d in dates_parsed)
            self.total_impressions = sum(d["impressions"] for d in dates_parsed)
            
            # Media ponderada para CTR y posición
            if self.total_impressions > 0:
                self.avg_ctr = (self.total_clicks / self.total_impressions) * 100
                total_pos_prod = sum(d["position"] * d["impressions"] for d in dates_parsed)
                self.avg_position = total_pos_prod / self.total_impressions
            else:
                self.avg_ctr = 0.0
                self.avg_position = 0.0

            log_info(f"KPIs calculados: Clics={self.total_clicks}, Imp={self.total_impressions}, CTR={self.avg_ctr:.2f}%, Pos={self.avg_position:.2f}")

    def process_queries(self):
        if "queries" not in self.extracted_files:
            return

        csv_rows = read_csv_safely(self.extracted_files["queries"])
        if not csv_rows or len(csv_rows) < 2:
            return

        headers = csv_rows[0]
        col_map = self.map_headers(headers)
        
        required = ("query", "clicks", "impressions", "ctr", "position")
        if not all(k in col_map for k in required):
            return

        for row in csv_rows[1:]:
            if len(row) <= max(col_map.values()):
                continue
            query = row[col_map["query"]]
            if not query or query.lower() in ("total", "totales"):
                continue
            clicks = int(clean_num(row[col_map["clicks"]]))
            impressions = int(clean_num(row[col_map["impressions"]]))
            ctr = clean_num(row[col_map["ctr"]])
            position = clean_num(row[col_map["position"]])
            
            self.queries_data.append({
                "query": query,
                "clicks": clicks,
                "impressions": impressions,
                "ctr": ctr,
                "position": position
            })

    def process_pages(self):
        if "pages" not in self.extracted_files:
            return

        csv_rows = read_csv_safely(self.extracted_files["pages"])
        if not csv_rows or len(csv_rows) < 2:
            return

        headers = csv_rows[0]
        col_map = self.map_headers(headers)
        
        required = ("page", "clicks", "impressions", "ctr", "position")
        if not all(k in col_map for k in required):
            return

        for row in csv_rows[1:]:
            if len(row) <= max(col_map.values()):
                continue
            page = row[col_map["page"]]
            if not page or page.lower() in ("total", "totales"):
                continue
            clicks = int(clean_num(row[col_map["clicks"]]))
            impressions = int(clean_num(row[col_map["impressions"]]))
            ctr = clean_num(row[col_map["ctr"]])
            position = clean_num(row[col_map["position"]])
            
            # Tratar de inferir el dominio
            if page.startswith("http"):
                try:
                    from urllib.parse import urlparse
                    self.domain = urlparse(page).netloc
                except Exception:
                    pass
            
            self.pages_data.append({
                "page": page,
                "clicks": clicks,
                "impressions": impressions,
                "ctr": ctr,
                "position": position
            })

    def process_devices(self):
        if "devices" not in self.extracted_files:
            return

        csv_rows = read_csv_safely(self.extracted_files["devices"])
        if not csv_rows or len(csv_rows) < 2:
            return

        headers = csv_rows[0]
        col_map = self.map_headers(headers)
        
        required = ("device", "clicks", "impressions", "ctr", "position")
        if not all(k in col_map for k in required):
            return

        for row in csv_rows[1:]:
            if len(row) <= max(col_map.values()):
                continue
            device = row[col_map["device"]]
            clicks = int(clean_num(row[col_map["clicks"]]))
            impressions = int(clean_num(row[col_map["impressions"]]))
            ctr = clean_num(row[col_map["ctr"]])
            position = clean_num(row[col_map["position"]])
            
            # Traducir dispositivo
            dev_lower = device.lower()
            if "desktop" in dev_lower or "ordenador" in dev_lower:
                device_es = "Ordenador"
            elif "mobile" in dev_lower or "móvil" in dev_lower or "movil" in dev_lower:
                device_es = "Móvil"
            elif "tablet" in dev_lower:
                device_es = "Tablet"
            else:
                device_es = device
                
            self.devices_data.append({
                "device": device_es,
                "clicks": clicks,
                "impressions": impressions,
                "ctr": ctr,
                "position": position
            })

    def generate_pdf(self):
        # 1. Preparar agregaciones para las gráficas
        # Agrupar por mes
        monthly = {}
        for d in self.dates_data:
            month_key = d["date"].strftime("%Y-%m")
            if month_key not in monthly:
                monthly[month_key] = {"clicks": 0, "impressions": 0}
            monthly[month_key]["clicks"] += d["clicks"]
            monthly[month_key]["impressions"] += d["impressions"]

        # Ordenar meses
        sorted_months = sorted(monthly.keys())
        
        # Escribir archivo de datos para PGFPlots
        dat_path = os.path.join(self.temp_dir, "monthly_data.dat")
        with open(dat_path, "w", encoding="utf-8") as f:
            f.write("month clicks impressions\n")
            for idx, m in enumerate(sorted_months):
                f.write(f"{idx} {monthly[m]['clicks']} {monthly[m]['impressions']}\n")

        # 2. Filtrar Palabras Oportunidad (Posición 11 a 20 con más impresiones)
        opp_queries = [q for q in self.queries_data if 10.5 <= q["position"] <= 20.5]
        opp_queries.sort(key=lambda x: x["impressions"], reverse=True)
        top_opps = opp_queries[:10]

        # Top 10 Consultas
        top_queries = sorted(self.queries_data, key=lambda x: x["clicks"], reverse=True)[:10]

        # Top 10 Páginas
        top_pages = sorted(self.pages_data, key=lambda x: x["clicks"], reverse=True)[:10]

        # Formatear fechas de cobertura
        date_range_str = "N/A"
        if self.start_date and self.end_date:
            date_range_str = f"del {self.start_date.strftime('%d/%m/%Y')} al {self.end_date.strftime('%d/%m/%Y')}"

        # 3. Escribir plantilla LaTeX
        tex_path = os.path.join(self.temp_dir, "report.tex")
        
        # Escapar caracteres de LaTeX
        def tex_esc(s):
            if not s:
                return ""
            return str(s).replace("&", "\\&").replace("%", "\\%").replace("_", "\\_").replace("#", "\\#").replace("$", "\\$")

        def shorten_url(url):
            url = str(url)
            # Quitar http/https y www
            url = url.replace("https://", "").replace("http://", "").replace("www.", "")
            if len(url) > 50:
                return url[:47] + "..."
            return url

        # Definir estructura LaTeX
        latex_code = r"""\documentclass[11pt,a4paper]{article}
\usepackage[utf8]{inputenc}
\usepackage[spanish]{babel}
\usepackage{geometry}
\geometry{a4paper, margin=2.2cm, top=2.5cm, bottom=2.5cm}
\usepackage{graphicx}
\usepackage{booktabs}
\usepackage{xcolor}
\usepackage{fancyhdr}
\usepackage{pgfplots}
\pgfplotsset{compat=1.17}
\usepackage{colortbl}
\usepackage{tcolorbox}
\usepackage{hyperref}

% Paleta de colores Corporativos
\definecolor{brandorange}{HTML}{E8681A}
\definecolor{branddark}{HTML}{22313F}
\definecolor{lightorange}{HTML}{FDF2EC}
\definecolor{lightgray}{HTML}{F8F9FA}
\definecolor{bordergray}{HTML}{E5E7EB}

\hypersetup{
    colorlinks=true,
    linkcolor=branddark,
    urlcolor=brandorange
}

% Cabecera y Pie de página
\pagestyle{fancy}
\fancyhf{}
\renewcommand{\headrulewidth}{0.5pt}
\renewcommand{\footrulewidth}{0.5pt}
\fancyhead[L]{\textcolor{branddark}{\textbf{\small """ + tex_esc(self.domain.upper()) + r""" | Informe SEO}}}
\fancyhead[R]{\textcolor{brandorange}{\small Víctor Alonso SEO}}
\fancyfoot[L]{\textcolor{branddark}{\small \href{https://www.victor-alonso.es}{victor-alonso.es} · Consultor SEO técnico}}
\fancyfoot[R]{\textcolor{branddark}{\small Página \thepage}}

\begin{document}

% --- PORTADA ---
\thispagestyle{empty}
\begin{center}
    \vspace*{1.5cm}
    % Logo (Si existe, si no se dibuja una bonita caja)
    """ + (r"""\includegraphics[width=2.5cm]{logo.png}""" if self.logo_exists else r"""\begin{tcolorbox}[colback=brandorange, colframe=brandorange, arc=5mm, width=2.5cm, height=2.5cm, halign=center, valign=center]
        \color{white}\bfseries\Huge VA
    \end{tcolorbox}""") + r"""
    \vspace{1cm}
    
    {\Huge\color{branddark}\bfseries AUDITORÍA DE RENDIMIENTO ORGÁNICO}\\[0.4cm]
    {\large\color{brandorange}\bfseries Google Search Console \& Estrategia de Crecimiento}\\[1.5cm]
    
    \begin{tcolorbox}[colback=lightgray, colframe=bordergray, arc=3mm, boxrule=1pt, width=0.85\textwidth, left=1cm, right=1cm, top=0.8cm, bottom=0.8cm]
        \begin{tabular}{ll}
            \textbf{\color{branddark}Sitio Web:} & \large\href{https://""" + tex_esc(self.domain) + r"""}{""" + tex_esc(self.domain) + r"""} \\
            \textbf{\color{branddark}Periodo Analizado:} & """ + tex_esc(date_range_str) + r""" \\
            \textbf{\color{branddark}Fecha del Informe:} & """ + datetime.now().strftime("%d/%m/%Y") + r""" \\
            \textbf{\color{branddark}Analista:} & Víctor Alonso SEO (\href{mailto:soy@victor-alonso.es}{soy@victor-alonso.es})
        \end{tabular}
    \end{tcolorbox}
    
    \vfill
    \textcolor{branddark}{\small He preparado este informe con información confidencial de tu Search Console. Las recomendaciones se basan en mi análisis cuantitativo de tus datos de tráfico en Google y están orientadas a optimizar tu visibilidad orgánica On-Page y tu indexación técnica.}
    
    \vspace*{1cm}
\end{center}
\newpage

% --- RESUMEN EJECUTIVO ---
\section{Resumen Ejecutivo (KPIs Globales)}
Mi análisis de rendimiento orgánico refleja el estado de indexación y clics en las páginas de búsqueda de Google. A continuación, te presento los principales indicadores clave de rendimiento (KPIs) del periodo analizado:

\vspace{0.4cm}
\begin{center}
\begin{tabular}{cccc}
    \begin{tcolorbox}[colback=lightorange, colframe=brandorange, width=0.22\textwidth, halign=center, arc=2mm, boxrule=1.5pt, top=0.3cm, bottom=0.3cm]
        \color{branddark}\bfseries Clics Totales\\
        \vspace{0.15cm}
        \Huge\color{brandorange}\bfseries """ + f"{self.total_clicks:,}".replace(",", ".") + r"""
    \end{tcolorbox} &
    \begin{tcolorbox}[colback=lightgray, colframe=branddark, width=0.22\textwidth, halign=center, arc=2mm, boxrule=1.5pt, top=0.3cm, bottom=0.3cm]
        \color{branddark}\bfseries Impresiones\\
        \vspace{0.15cm}
        \Huge\color{branddark}\bfseries """ + f"{self.total_impressions:,}".replace(",", ".") + r"""
    \end{tcolorbox} &
    \begin{tcolorbox}[colback=lightgray, colframe=branddark, width=0.22\textwidth, halign=center, arc=2mm, boxrule=1.5pt, top=0.3cm, bottom=0.3cm]
        \color{branddark}\bfseries CTR Promedio\\
        \vspace{0.15cm}
        \Huge\color{branddark}\bfseries """ + f"{self.avg_ctr:.2f}\\%" + r"""
    \end{tcolorbox} &
    \begin{tcolorbox}[colback=lightgray, colframe=branddark, width=0.22\textwidth, halign=center, arc=2mm, boxrule=1.5pt, top=0.3cm, bottom=0.3cm]
        \color{branddark}\bfseries Posición Media\\
        \vspace{0.15cm}
        \Huge\color{branddark}\bfseries """ + f"{self.avg_position:.1f}" + r"""
    \end{tcolorbox}
\end{tabular}
\end{center}

\vspace{0.5cm}
\subsection{Distribución por Dispositivo}
El rendimiento por dispositivo te revela la experiencia de búsqueda predominante de tu audiencia. Comparo estos datos ya que el CTR móvil suele variar considerablemente por la disposición de las pantallas.

\vspace{0.2cm}
\begin{center}
\begin{tabular}{lrrrr}
    \toprule
    \textbf{Dispositivo} & \textbf{Clics} & \textbf{Impresiones} & \textbf{CTR Promedio} & \textbf{Posición Media} \\
    \midrule
"""
        for d in self.devices_data:
            latex_code += f"    {tex_esc(d['device'])} & {d['clicks']:,} & {d['impressions']:,} & {d['ctr']:.2f}\\% & {d['position']:.1f} \\\\\n".replace(",", ".")
            
        latex_code += r"""    \bottomrule
\end{tabular}
\end{center}

\newpage

% --- EVOLUCIÓN TEMPORAL ---
\section{Evolución de Clics e Impresiones}
En esta gráfica te muestro la tendencia mensual agregada de los clics recibidos desde el buscador Google. Te servirá para identificar de manera visual si tu estrategia está ganando tracción y visibilidad.

\vspace{0.8cm}
\begin{center}
\begin{tikzpicture}
\begin{axis}[
    width=0.9\textwidth,
    height=6.5cm,
    axis y line*=left,
    xlabel={\textbf{Meses del Periodo}},
    ylabel={\textcolor{brandorange}{\textbf{Clics}}},
    ylabel style={yshift=0.2cm},
    xtick=data,
    xticklabels={""" + ", ".join([f"{{{m}}}" for m in sorted_months]) + r"""},
    x tick label style={rotate=35, anchor=north east, font=\small},
    grid=both,
    grid style={line width=.1pt, color=gray!10},
    major grid style={line width=.2pt, color=gray!20},
    tick align=outside,
    tickpos=left
]
\addplot[color=brandorange, mark=*, line width=1.5pt] table[x=month, y=clicks] {monthly_data.dat};
\end{axis}

\begin{axis}[
    width=0.9\textwidth,
    height=6.5cm,
    axis y line*=right,
    axis x line=none,
    ylabel={\textcolor{branddark}{\textbf{Impresiones}}},
    ylabel style={yshift=-0.2cm},
    xtick=data,
    grid=none
]
\addplot[color=branddark, mark=x, line width=1.2pt, dashed] table[x=month, y=impressions] {monthly_data.dat};
\end{axis}
\end{tikzpicture}
\end{center}

\vspace{0.5cm}
* \textbf{Nota de tendencia:} La línea continua naranja representa los clics de tráfico real, mientras que la línea discontinua oscura muestra la visibilidad total (impresiones). Un aumento de impresiones no correspondido por clics suele indicar la necesidad de optimizar los fragmentos SERP (títulos y descripciones).

\section{Páginas con Mayor Rendimiento}
He identificado las 10 páginas de tu sitio que concentran más clics directos en Google. Considero que constituyen el núcleo de autoridad y tu principal fuente de atracción de negocio.

\vspace{0.3cm}
\begin{center}
\begin{tabular}{lrrrr}
    \toprule
    \textbf{Ruta de la Página} & \textbf{Clics} & \textbf{Impresiones} & \textbf{CTR} & \textbf{Posición} \\
    \midrule
"""
        for p in top_pages:
            latex_code += f"    \\href{{{tex_esc(p['page'])}}}{{\\small {tex_esc(shorten_url(p['page']))}}} & {p['clicks']:,} & {p['impressions']:,} & {p['ctr']:.2f}\\% & {p['position']:.1f} \\\\\n".replace(",", ".")

        latex_code += r"""    \bottomrule
\end{tabular}
\end{center}

\newpage

% --- OPORTUNIDADES ---
\section{Palabras Clave de Alta Oportunidad (Limbo de Página 2)}
He seleccionado estas consultas porque están posicionadas entre la posición 11 y la 20 (página 2 de Google). Tienen un volumen de impresiones muy interesante, lo que significa que si las empujas a primera página con optimizaciones On-Page y enlazado interno, tu CTR y clics crecerán exponencialmente.

\vspace{0.4cm}
\begin{center}
\begin{tabular}{lrrrr}
    \toprule
    \textbf{Consulta de Búsqueda} & \textbf{Clics} & \textbf{Impresiones} & \textbf{CTR} & \textbf{Posición} \\
    \midrule
"""
        if not top_opps:
            latex_code += r"    \multicolumn{5}{c}{No se encontraron consultas suficientes en este rango de posiciones.} \\" + "\n"
        else:
            for q in top_opps:
                latex_code += f"    \\textbf{{{tex_esc(q['query'])}}} & {q['clicks']:,} & {q['impressions']:,} & {q['ctr']:.2f}\\% & {q['position']:.1f} \\\\\n".replace(",", ".")

        latex_code += r"""    \bottomrule
\end{tabular}
\end{center}

\section{Estrategia de Optimización Recomendada}
Basándome en mi análisis cuantitativo de tus datos de Search Console, te sugiero aplicar estas acciones de optimización:

\begin{enumerate}
    \item \textbf{Ataque Directo a Palabras Oportunidad:} 
          Te sugiero seleccionar las 3 palabras clave del bloque anterior que tengan mayor relevancia para tu negocio. Busca páginas o artículos donde las menciones y enriquécelos con encabezados H2 o H3 que resuelvan la intención de búsqueda exacta.
    \item \textbf{Mejora del CTR en tus Páginas Top:}
          Revisa las páginas de tu web con más tráfico que tengan un CTR bajo. Te recomiendo redactar descripciones meta más atractivas, incluyendo una llamada a la acción clara para captar más clics frente a la competencia.
    \item \textbf{Optimización del Enlazado Interno:}
          Te aconsejo distribuir la autoridad interna de tus herramientas con más visitas hacia tu página de inicio u otras páginas críticas. Evita anchors de coincidencia exacta repetitivos y varía los textos de forma natural (ej. «consultor SEO técnico», «auditoría web», «Víctor Alonso SEO»).
    \end{enumerate}

\vspace{1.5cm}
\begin{tcolorbox}[colback=lightorange, colframe=brandorange, arc=3mm, title=\textbf{¿Necesitas ayuda para ejecutar estas mejoras?}]
\color{branddark}
La interpretación de datos de Search Console es solo el primer paso. Si no dispones de tiempo o conocimientos técnicos para implementar estas recomendaciones de arquitectura, WPO o enlazado interno, estaré encantado de ayudarte. Puedes contar con mis servicios de consultoría.

Escríbeme sin compromiso a \href{mailto:soy@victor-alonso.es}{soy@victor-alonso.es} o a través de \href{https://www.victor-alonso.es/contacto/}{victor-alonso.es/contacto/} para coordinar una consultoría técnica.
\end{tcolorbox}

\end{document}
"""

        with open(tex_path, "w", encoding="utf-8") as f:
            f.write(latex_code)
        log_info("Archivo LaTeX generado correctamente.")

        # 4. Compilar LaTeX a PDF
        # pdflatex necesita ejecutarse 2 veces para generar los gráficos PGFPlots y referencias cruzadas
        for run in (1, 2):
            log_info(f"Compilando LaTeX (Pasada {run}/2)...")
            cmd = [
                "pdflatex",
                "-interaction=nonstopmode",
                f"-output-directory={self.temp_dir}",
                tex_path
            ]
            result = subprocess.run(cmd, stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True)
            if result.returncode != 0:
                log_error(f"Fallo en la compilación de LaTeX en la pasada {run}:")
                log_error(result.stdout)
                log_error(result.stderr)
                # Inspeccionar el archivo log si hay fallo
                log_file_path = os.path.join(self.temp_dir, "report.log")
                if os.path.exists(log_file_path):
                    with open(log_file_path, "r", encoding="utf-8", errors="ignore") as lf:
                        log_info("Detalle de las últimas 30 líneas del LOG:")
                        print("\n".join(lf.readlines()[-30:]))
                raise RuntimeError("Error en compilación LaTeX.")

        # 5. Copiar PDF final a su destino
        compiled_pdf = os.path.join(self.temp_dir, "report.pdf")
        if not os.path.exists(compiled_pdf):
            raise FileNotFoundError("El PDF compilado no se encontró en la carpeta temporal.")

        # Asegurar directorio de destino
        dest_dir = os.path.dirname(self.output_pdf)
        if dest_dir:
            os.makedirs(dest_dir, exist_ok=True)
            
        shutil.copy2(compiled_pdf, self.output_pdf)
        log_info(f"PDF copiado correctamente a {self.output_pdf}")

if __name__ == "__main__":
    if len(sys.argv) < 3:
        log_error("Uso: engine.py <path_zip> <path_output_pdf>")
        sys.exit(1)
        
    zip_p = sys.argv[1]
    pdf_p = sys.argv[2]
    
    if not os.path.exists(zip_p):
        log_error(f"Archivo ZIP no encontrado: {zip_p}")
        sys.exit(1)
        
    generator = GSCReportGenerator(zip_p, pdf_p)
    success = generator.execute()
    
    if success:
        log_info("Proceso finalizado con éxito.")
        sys.exit(0)
    else:
        log_error("Proceso fallido.")
        sys.exit(1)
