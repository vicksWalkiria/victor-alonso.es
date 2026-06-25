#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Log Analyzer PDF Report Engine
vicksWalkiria / victor-alonso.es
Genera un PDF profesional con LaTeX a partir del JSON de análisis de logs.
Uso: engine.py <path_json> <path_output_pdf>
"""

import os
import sys
import json
import tempfile
import shutil
import subprocess
from datetime import datetime

def log_info(msg):
    print(f"[INFO] {msg}")

def log_error(msg):
    print(f"[ERROR] {msg}", file=sys.stderr)

def tex_esc(s):
    """Escapa caracteres especiales de LaTeX."""
    if s is None:
        return ""
    s = str(s)
    replacements = [
        ("\\", "\\textbackslash{}"),
        ("&", "\\&"),
        ("%", "\\%"),
        ("$", "\\$"),
        ("#", "\\#"),
        ("_", "\\_"),
        ("{", "\\{"),
        ("}", "\\}"),
        ("~", "\\textasciitilde{}"),
        ("^", "\\textasciicircum{}"),
    ]
    for old, new in replacements:
        if old == "\\":
            s = s.replace(old, new)
        else:
            s = s.replace(old, new)
    return s

def shorten_url(url, max_len=48):
    url = str(url)
    url = url.replace("https://", "").replace("http://", "").replace("www.", "")
    if len(url) > max_len:
        return url[:max_len - 3] + "..."
    return url

def fmt_num(n):
    """Formatea número con puntos como separador de miles (español)."""
    try:
        return f"{int(n):,}".replace(",", ".")
    except Exception:
        return str(n)

class LogReportGenerator:
    def __init__(self, json_path, output_pdf):
        self.json_path = json_path
        self.output_pdf = output_pdf
        self.temp_dir = None
        self.data = {}
        self.logo_exists = False

    def execute(self):
        try:
            self.temp_dir = tempfile.mkdtemp(prefix="log_report_")
            log_info(f"Directorio temporal: {self.temp_dir}")

            # Copiar logo corporativo si existe
            script_dir = os.path.dirname(os.path.abspath(__file__))
            project_root = os.path.dirname(os.path.dirname(script_dir))
            logo_src = os.path.join(project_root, "apple-touch-icon.png")
            if os.path.exists(logo_src):
                shutil.copy2(logo_src, os.path.join(self.temp_dir, "logo.png"))
                self.logo_exists = True
                log_info("Logo copiado.")

            # Cargar JSON
            with open(self.json_path, "r", encoding="utf-8") as f:
                self.data = json.load(f)
            log_info(f"JSON cargado: {self.json_path}")

            self.generate_pdf()
            return True
        except Exception as e:
            log_error(f"Fallo en el generador: {e}")
            import traceback
            traceback.print_exc()
            return False
        finally:
            if self.temp_dir and os.path.exists(self.temp_dir):
                shutil.rmtree(self.temp_dir)
                log_info("Limpieza temporal completada.")

    def generate_pdf(self):
        d = self.data

        source_name   = d.get("source_name", "Log de servidor")
        parsed_lines  = d.get("parsed_lines", 0)
        unique_ips    = d.get("unique_ips_count", 0)
        bandwidth_mb  = d.get("bandwidth_mb", 0)
        date_start    = d.get("date_start", "")
        date_end      = d.get("date_end", "")
        status_codes  = d.get("status_codes", {})
        top_ips       = d.get("top_ips", {})
        top_urls_ns   = d.get("top_urls_no_static", {})
        top_urls      = d.get("top_urls", {})
        top_bots      = d.get("top_bots", {})
        top_404s      = d.get("top_404s", {})
        hourly        = d.get("hourly_distribution", [0]*24)

        # Agrupar status codes en familias
        s2xx = s3xx = s4xx = s5xx = 0
        for code, cnt in status_codes.items():
            c = str(code)
            if c.startswith("2"): s2xx += cnt
            elif c.startswith("3"): s3xx += cnt
            elif c.startswith("4"): s4xx += cnt
            elif c.startswith("5"): s5xx += cnt

        total_requests = max(parsed_lines, 1)
        p2xx = round(s2xx / total_requests * 100, 1)
        p3xx = round(s3xx / total_requests * 100, 1)
        p4xx = round(s4xx / total_requests * 100, 1)
        p5xx = round(s5xx / total_requests * 100, 1)

        # Datos para gráfica hourly (normalizado 0-100)
        max_hour = max(hourly) if hourly and max(hourly) > 0 else 1
        hourly_norm = [round(h / max_hour * 100, 1) for h in hourly]

        # Hora pico
        peak_hour = hourly.index(max(hourly)) if hourly else 0
        peak_hits = max(hourly) if hourly else 0

        # Fecha corta del informe
        report_date = datetime.now().strftime("%d/%m/%Y")

        # Formatear rango de fechas
        def fmt_log_date(s):
            if not s:
                return "N/A"
            parts = s.split(" ")[0]  # "24/May/2026:11:45:22"
            date_part = parts.split(":")[0]  # "24/May/2026"
            months = {"Jan":"Enero","Feb":"Febrero","Mar":"Marzo","Apr":"Abril","May":"Mayo",
                      "Jun":"Junio","Jul":"Julio","Aug":"Agosto","Sep":"Septiembre",
                      "Oct":"Octubre","Nov":"Noviembre","Dec":"Diciembre"}
            dp = date_part.split("/")
            if len(dp) == 3:
                return f"{dp[0]} de {months.get(dp[1], dp[1])} de {dp[2]}"
            return s

        date_start_fmt = fmt_log_date(date_start)
        date_end_fmt   = fmt_log_date(date_end)

        # Escribir datos hourly para PGFPlots
        dat_path = os.path.join(self.temp_dir, "hourly.dat")
        with open(dat_path, "w", encoding="utf-8") as f:
            f.write("hour hits\n")
            for i, h in enumerate(hourly):
                f.write(f"{i} {h}\n")

        # ── Construir LaTeX ──────────────────────────────────────────────────────
        logo_block = (
            r"\includegraphics[width=2.5cm]{logo.png}"
            if self.logo_exists else
            r"""\begin{tcolorbox}[colback=brandorange,colframe=brandorange,arc=5mm,width=2.5cm,height=2.5cm,halign=center,valign=center]
    \color{white}\bfseries\Huge VA
\end{tcolorbox}"""
        )

        # Filas top URLs indexables
        url_rows = ""
        for url, hits in list(top_urls_ns.items())[:10]:
            pct = round(hits / total_requests * 100, 1)
            url_rows += f"    \\texttt{{\\small {tex_esc(shorten_url(url))}}} & {fmt_num(hits)} & {pct}\\% \\\\\n"
        if not url_rows:
            url_rows = "    \\multicolumn{3}{c}{Sin datos disponibles} \\\\\n"

        # Filas errores 404
        e404_rows = ""
        for url, hits in list(top_404s.items())[:10]:
            pct = round(hits / total_requests * 100, 1)
            e404_rows += f"    \\texttt{{\\small\\color{{red}} {tex_esc(shorten_url(url))}}} & {fmt_num(hits)} & {pct}\\% \\\\\n"
        if not e404_rows:
            e404_rows = "    \\multicolumn{3}{c}{\\color{green!60!black}\\textbf{\\checkmark~¡Sin errores 404 detectados!}} \\\\\n"

        # Filas bots
        bot_rows = ""
        for bot, hits in list(top_bots.items())[:8]:
            pct = round(hits / total_requests * 100, 1)
            bot_rows += f"    {tex_esc(bot)} & {fmt_num(hits)} & {pct}\\% \\\\\n"
        if not bot_rows:
            bot_rows = "    \\multicolumn{3}{c}{No se detectaron bots conocidos} \\\\\n"

        # Filas IPs
        ip_rows = ""
        for ip, hits in list(top_ips.items())[:8]:
            pct = round(hits / total_requests * 100, 1)
            ip_rows += f"    \\texttt{{{tex_esc(ip)}}} & {fmt_num(hits)} & {pct}\\% \\\\\n"
        if not ip_rows:
            ip_rows = "    \\multicolumn{3}{c}{Sin datos} \\\\\n"

        # Análisis textual
        health_score = "buena" if p4xx < 5 and p5xx < 1 else "mejorable"
        bot_names = list(top_bots.keys())[:3]
        bot_text = ", ".join([tex_esc(b) for b in bot_names]) if bot_names else "ninguno detectado"
        top404_list = list(top_404s.keys())[:2]
        error_text = (
            f"Las rutas con más errores 404 son: \\texttt{{{tex_esc(shorten_url(top404_list[0]))}}} "
            + (f"y \\texttt{{{tex_esc(shorten_url(top404_list[1]))}}}." if len(top404_list) > 1 else ".")
        ) if top404_list else "No se detectaron errores 404 relevantes. ¡Excelente!"

        latex = r"""\documentclass[11pt,a4paper]{article}
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
\usepackage{amssymb}

\definecolor{brandorange}{HTML}{E8681A}
\definecolor{branddark}{HTML}{22313F}
\definecolor{lightorange}{HTML}{FDF2EC}
\definecolor{lightgray}{HTML}{F8F9FA}
\definecolor{bordergray}{HTML}{E5E7EB}
\definecolor{green60black}{HTML}{1A8A1A}

\hypersetup{colorlinks=true, linkcolor=branddark, urlcolor=brandorange}

\pagestyle{fancy}
\fancyhf{}
\renewcommand{\headrulewidth}{0.5pt}
\renewcommand{\footrulewidth}{0.5pt}
\fancyhead[L]{\textcolor{branddark}{\textbf{\small """ + tex_esc(source_name) + r""" | Informe de Logs}}}
\fancyhead[R]{\textcolor{brandorange}{\small Víctor Alonso SEO}}
\fancyfoot[L]{\textcolor{branddark}{\small \href{https://www.victor-alonso.es}{victor-alonso.es} · Analizador de Logs}}
\fancyfoot[R]{\textcolor{branddark}{\small Página \thepage}}

\begin{document}

%% ── PORTADA ────────────────────────────────────────────────────────────────
\thispagestyle{empty}
\begin{center}
  \vspace*{1.5cm}
  """ + logo_block + r"""
  \vspace{1cm}

  {\Huge\color{branddark}\bfseries ANÁLISIS DE LOGS DE SERVIDOR}\\[0.4cm]
  {\large\color{brandorange}\bfseries Auditoría de Rastreo, Errores y Crawl Budget}\\[1.5cm]

  \begin{tcolorbox}[colback=lightgray, colframe=bordergray, arc=3mm, boxrule=1pt,
    width=0.85\textwidth, left=1cm, right=1cm, top=0.8cm, bottom=0.8cm]
    \begin{tabular}{ll}
      \textbf{\color{branddark}Archivo analizado:} & \texttt{\small """ + tex_esc(source_name) + r"""} \\
      \textbf{\color{branddark}Inicio del log:}    & """ + tex_esc(date_start_fmt) + r""" \\
      \textbf{\color{branddark}Fin del log:}       & """ + tex_esc(date_end_fmt) + r""" \\
      \textbf{\color{branddark}Fecha del informe:} & """ + report_date + r""" \\
      \textbf{\color{branddark}Generado en:}       & \href{https://www.victor-alonso.es/herramientas/analizador-logs/}{victor-alonso.es}
    \end{tabular}
  \end{tcolorbox}

  \vfill
  \textcolor{branddark}{\small Este informe ha sido generado automáticamente a partir
  de tu log de accesos Apache/Nginx. Los datos son procesados en memoria y eliminados
  del servidor tras la generación. Tu privacidad es garantizada.}
  \vspace*{1cm}
\end{center}
\newpage

%% ── KPIs GLOBALES ───────────────────────────────────────────────────────────
\section{Resumen Ejecutivo (KPIs Globales)}
Se han procesado \textbf{""" + fmt_num(parsed_lines) + r"""} peticiones del log \texttt{\small """ + tex_esc(source_name) + r"""},
identificando \textbf{""" + fmt_num(unique_ips) + r"""} direcciones IP únicas y un consumo de ancho de banda de
\textbf{""" + str(bandwidth_mb) + r""" MB} de datos servidos. El estado general de salud técnica es \textbf{""" + health_score + r"""}.

\vspace{0.4cm}
\begin{center}
\begin{tabular}{cccc}
  \begin{tcolorbox}[colback=lightorange, colframe=brandorange, width=0.22\textwidth,
    halign=center, arc=2mm, boxrule=1.5pt, top=0.3cm, bottom=0.3cm]
    \color{branddark}\bfseries Peticiones\\
    \vspace{0.15cm}
    \Large\color{brandorange}\bfseries """ + fmt_num(parsed_lines) + r"""
  \end{tcolorbox} &
  \begin{tcolorbox}[colback=lightgray, colframe=branddark, width=0.22\textwidth,
    halign=center, arc=2mm, boxrule=1.5pt, top=0.3cm, bottom=0.3cm]
    \color{branddark}\bfseries IPs Únicas\\
    \vspace{0.15cm}
    \Large\color{branddark}\bfseries """ + fmt_num(unique_ips) + r"""
  \end{tcolorbox} &
  \begin{tcolorbox}[colback=lightgray, colframe=branddark, width=0.22\textwidth,
    halign=center, arc=2mm, boxrule=1.5pt, top=0.3cm, bottom=0.3cm]
    \color{branddark}\bfseries Ancho de Banda\\
    \vspace{0.15cm}
    \Large\color{branddark}\bfseries """ + str(bandwidth_mb) + r""" MB
  \end{tcolorbox} &
  \begin{tcolorbox}[colback=lightgray, colframe=branddark, width=0.22\textwidth,
    halign=center, arc=2mm, boxrule=1.5pt, top=0.3cm, bottom=0.3cm]
    \color{branddark}\bfseries Errores 404\\
    \vspace{0.15cm}
    \Large\color{branddark}\bfseries """ + fmt_num(len(top_404s)) + r""" rutas
  \end{tcolorbox}
\end{tabular}
\end{center}

\subsection{Distribución de Códigos de Respuesta HTTP}
\begin{center}
\begin{tabular}{lrr}
  \toprule
  \textbf{Familia de Código} & \textbf{Peticiones} & \textbf{Porcentaje} \\
  \midrule
  \textbf{\color{green60black}2xx -- Éxito (OK)} & """ + fmt_num(s2xx) + r""" & """ + str(p2xx) + r"""\% \\
  \textbf{\color{orange}3xx -- Redirecciones} & """ + fmt_num(s3xx) + r""" & """ + str(p3xx) + r"""\% \\
  \textbf{\color{red}4xx -- Errores cliente} & """ + fmt_num(s4xx) + r""" & """ + str(p4xx) + r"""\% \\
  \textbf{\color{red!80!black}5xx -- Errores servidor} & """ + fmt_num(s5xx) + r""" & """ + str(p5xx) + r"""\% \\
  \bottomrule
\end{tabular}
\end{center}

\newpage

%% ── TIMELINE HORARIO ───────────────────────────────────────────────────────
\section{Distribución Temporal del Tráfico (por Hora)}
La hora de mayor actividad fue las \textbf{""" + str(peak_hour).zfill(2) + r""":00h}
con \textbf{""" + fmt_num(peak_hits) + r"""} peticiones.
Este dato es clave para identificar los momentos en que Googlebot o el tráfico real
concentra su actividad en tu servidor.

\vspace{0.5cm}
\begin{center}
\begin{tikzpicture}
\begin{axis}[
  width=0.95\textwidth,
  height=5.5cm,
  ybar,
  bar width=9pt,
  xlabel={\textbf{Hora del día}},
  ylabel={\textbf{Peticiones}},
  xtick={0,2,4,6,8,10,12,14,16,18,20,22},
  xticklabels={00h,02h,04h,06h,08h,10h,12h,14h,16h,18h,20h,22h},
  ymajorgrids=true,
  grid style={line width=.1pt, color=gray!15},
  tick align=outside,
  bar shift=0pt,
  every axis plot/.style={fill=brandorange!80, draw=brandorange}
]
\addplot table[x=hour, y=hits] {hourly.dat};
\end{axis}
\end{tikzpicture}
\end{center}

\newpage

%% ── URLs INDEXABLES ────────────────────────────────────────────────────────
\section{Top URLs Indexables (Sin Recursos Estáticos)}
Páginas HTML con mayor número de peticiones, filtradas de recursos estáticos (CSS, JS, imágenes).
Son las URLs que los bots de búsqueda rastrean con más frecuencia y las que impactan
directamente en tu crawl budget.

\vspace{0.3cm}
\begin{center}
\begin{tabular}{lrr}
  \toprule
  \textbf{URL / Ruta} & \textbf{Peticiones} & \textbf{\%} \\
  \midrule
""" + url_rows + r"""  \bottomrule
\end{tabular}
\end{center}

%% ── ERRORES 404 ────────────────────────────────────────────────────────────
\section{Errores 404 Detectados}
Los errores 404 consumen crawl budget sin aportar valor indexable. Si alguna de estas
rutas tiene enlaces entrantes o fue una URL relevante, aplica una redirección 301 inmediata.
""" + error_text + r"""

\vspace{0.3cm}
\begin{center}
\begin{tabular}{lrr}
  \toprule
  \textbf{Ruta con Error 404} & \textbf{Ocurrencias} & \textbf{\%} \\
  \midrule
""" + e404_rows + r"""  \bottomrule
\end{tabular}
\end{center}

\newpage

%% ── BOTS ───────────────────────────────────────────────────────────────────
\section{Actividad de Bots y Crawlers de Búsqueda}
Los crawlers identificados en este log son: \textbf{""" + bot_text + r"""}.
Analizar la frecuencia de rastreo de Googlebot te permite detectar si está
desperdiciando presupuesto en secciones sin valor o si, por el contrario,
visita correctamente tus contenidos más importantes.

\vspace{0.3cm}
\begin{center}
\begin{tabular}{lrr}
  \toprule
  \textbf{Bot / Crawler} & \textbf{Peticiones} & \textbf{\%} \\
  \midrule
""" + bot_rows + r"""  \bottomrule
\end{tabular}
\end{center}

\subsection{Top IPs más Activas}
\vspace{0.2cm}
\begin{center}
\begin{tabular}{lrr}
  \toprule
  \textbf{Dirección IP} & \textbf{Hits} & \textbf{\%} \\
  \midrule
""" + ip_rows + r"""  \bottomrule
\end{tabular}
\end{center}

\newpage

%% ── RECOMENDACIONES ────────────────────────────────────────────────────────
\section{Recomendaciones Técnicas SEO}

\begin{enumerate}
  \item \textbf{Gestión del Crawl Budget:}
    Si ves que bots como Googlebot rastrean URLs de parámetros, secciones de filtros
    o recursos sin valor (fichas duplicadas, paginaciones), bloquéalas en el
    \texttt{robots.txt} o mediante el uso de la etiqueta \texttt{noindex}.
    Cada petición de bot a una URL sin valor es presupuesto desperdiciado.

  \item \textbf{Resolución de Errores 404:}
    Implementa redirecciones 301 para todas las rutas con errores 404 que dispongan
    de enlaces externos o que anteriormente tuviesen tráfico. Usa \texttt{.htaccess}
    (Apache) o las directivas \texttt{rewrite} de tu configuración Nginx para
    gestionarlo de forma centralizada.

  \item \textbf{Vigilancia de IPs Sospechosas:}
    Si una IP concentra miles de peticiones en un periodo corto y no corresponde
    a un bot legítimo (Googlebot, Bingbot...), es probablemente un scraper o un
    ataque de fuerza bruta. Bloquéala en tu firewall (Cloudflare, \texttt{.htaccess})
    o mediante \texttt{fail2ban}.

  \item \textbf{Optimización del Servidor en Horas Pico:}
    El pico de tráfico detectado a las \textbf{""" + str(peak_hour).zfill(2) + r""":00h} puede
    saturar los recursos del servidor si coincide con rastreos masivos de bots.
    Considera activar el caché de página completa (Varnish, WP Rocket, etc.) para
    amortiguar el impacto sin degradar los tiempos de respuesta.
\end{enumerate}

\vspace{1.2cm}
\begin{tcolorbox}[colback=lightorange, colframe=brandorange, arc=3mm,
  title={\textbf{¿Necesitas ayuda para interpretar o aplicar estas mejoras?}}]
\color{branddark}
El análisis de logs es una de las herramientas más potentes del SEO técnico avanzado.
Si detectas patrones anómalos, errores recurrentes o un gasto de crawl budget excesivo
y no sabes cómo atacarlos, estaré encantado de ayudarte con una auditoría personalizada.

Escríbeme sin compromiso a \href{mailto:soy@victor-alonso.es}{soy@victor-alonso.es}
o desde \href{https://www.victor-alonso.es/contacto/}{victor-alonso.es/contacto/}.
\end{tcolorbox}

\end{document}
"""

        tex_path = os.path.join(self.temp_dir, "report.tex")
        with open(tex_path, "w", encoding="utf-8") as f:
            f.write(latex)
        log_info("LaTeX generado correctamente.")

        # Compilar 2 pasadas (necesario para PGFPlots + referencias)
        for run in (1, 2):
            log_info(f"Compilando LaTeX (pasada {run}/2)...")
            cmd = [
                "pdflatex",
                "-interaction=nonstopmode",
                f"-output-directory={self.temp_dir}",
                tex_path
            ]
            result = subprocess.run(cmd, stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True, cwd=self.temp_dir)
            if result.returncode != 0:
                log_error(f"Fallo compilación LaTeX pasada {run}:")
                log_error(result.stdout[-3000:])
                lf_path = os.path.join(self.temp_dir, "report.log")
                if os.path.exists(lf_path):
                    with open(lf_path, "r", encoding="utf-8", errors="ignore") as lf:
                        print("\n".join(lf.readlines()[-40:]))
                raise RuntimeError("Error compilando LaTeX.")

        compiled_pdf = os.path.join(self.temp_dir, "report.pdf")
        if not os.path.exists(compiled_pdf):
            raise FileNotFoundError("PDF compilado no encontrado.")

        os.makedirs(os.path.dirname(self.output_pdf), exist_ok=True)
        shutil.copy2(compiled_pdf, self.output_pdf)
        log_info(f"PDF guardado en: {self.output_pdf}")


if __name__ == "__main__":
    if len(sys.argv) < 3:
        log_error("Uso: engine.py <path_json> <path_output_pdf>")
        sys.exit(1)

    json_p = sys.argv[1]
    pdf_p  = sys.argv[2]

    if not os.path.exists(json_p):
        log_error(f"JSON no encontrado: {json_p}")
        sys.exit(1)

    gen = LogReportGenerator(json_p, pdf_p)
    success = gen.execute()
    sys.exit(0 if success else 1)
