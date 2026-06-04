/* ERP Charts — Chart.js wrapper with theme-aware color palette */
(function() {
  'use strict';

  /* Hardcoded fallbacks in case CSS variables aren't loaded yet */
  var FALLBACK_PALETTE = ['#3D52A0','#7091E6','#10b981','#f59e0b','#ef4444','#8b5cf6','#06b6d4','#8697C4'];
  var FALLBACK_PALETTE_LIGHT = ['#7091E6','#93c5fd','#6ee7b7','#fcd34d','#fca5a5','#c4b5fd','#67e8f9','#ADBBDA'];

  /* Read a CSS custom property from :root */
  function readCSSVar(name) {
    return getComputedStyle(document.documentElement).getPropertyValue(name).trim();
  }

  /* Build palette from CSS variables (auto-adapts to light/dark via CSS) */
  function getChartPalette() {
    var result = [
      readCSSVar('--erp-chart-1'), readCSSVar('--erp-chart-2'),
      readCSSVar('--erp-chart-3'), readCSSVar('--erp-chart-4'),
      readCSSVar('--erp-chart-5'), readCSSVar('--erp-chart-6'),
      readCSSVar('--erp-chart-7'), readCSSVar('--erp-chart-8'),
    ];
    // Fall back if CSS vars are empty
    return result[0] ? result : FALLBACK_PALETTE;
  }

  function getChartPaletteLight() {
    var result = [
      readCSSVar('--erp-chart-1-light'), readCSSVar('--erp-chart-2-light'),
      readCSSVar('--erp-chart-3-light'), readCSSVar('--erp-chart-4-light'),
      readCSSVar('--erp-chart-5-light'), readCSSVar('--erp-chart-6-light'),
      readCSSVar('--erp-chart-7-light'), readCSSVar('--erp-chart-8-light'),
    ];
    return result[0] ? result : FALLBACK_PALETTE_LIGHT;
  }

  /* CSS vars already switch between light/dark, so no isDark() branching needed */
  function getPalette() {
    return getChartPalette();
  }

  function getPaletteLight() {
    return getChartPaletteLight();
  }

  /* Apply safe Chart.js defaults — only fonts and tooltips, never touch scales */
  function applyChartDefaults() {
    if (typeof Chart === 'undefined') return;

    Chart.defaults.font.family = "'Inter', sans-serif";
    Chart.defaults.font.size = 12;
    Chart.defaults.color = readCSSVar('--erp-chart-label') || '#8697C4';
    Chart.defaults.plugins.legend.labels.usePointStyle = true;
    Chart.defaults.plugins.legend.labels.padding = 16;
    Chart.defaults.plugins.tooltip.enabled = false;
    Chart.defaults.plugins.tooltip.external = createExternalTooltip;
    Chart.defaults.elements.line.tension = 0.35;
    Chart.defaults.elements.point.radius = 3;
    Chart.defaults.elements.point.hoverRadius = 6;
  }

  applyChartDefaults();

  /* Re-apply on dark mode toggle */
  var observer = new MutationObserver(function(mutations) {
    mutations.forEach(function(m) {
      if (m.attributeName === 'class') {
        applyChartDefaults();
        if (typeof Chart !== 'undefined') {
          Object.values(Chart.instances).forEach(function(chart) {
            // Re-color datasets for new mode
            var palette = getPalette();
            var palLight = getPaletteLight();
            chart.data.datasets.forEach(function(ds, i) {
              var ci = i % palette.length;
              var type = chart.config.type;
              if (type === 'doughnut' || type === 'pie') {
                ds.backgroundColor = palette.slice(0, chart.data.labels.length);
                ds.borderColor = readCSSVar('--erp-chart-border') || '#fff';
              } else if (type === 'line' && ds.fill) {
                ds.borderColor = palette[ci];
                var ctx = chart.canvas.getContext('2d');
                ds.backgroundColor = ctx ? createGradientFill(ctx, palette[ci], chart.canvas.height || 300) : hexToRgba(palette[ci], 0.1);
                ds.pointBackgroundColor = palette[ci];
              } else if (type === 'line') {
                ds.borderColor = palette[ci];
                ds.pointBackgroundColor = palette[ci];
              } else {
                ds.backgroundColor = palette[ci];
                ds.hoverBackgroundColor = palLight[ci];
              }
            });
            chart.update();
          });
        }
      }
    });
  });
  observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });

  /**
   * erpChart(canvasId, type, data, opts)
   * type: 'bar' | 'line' | 'doughnut' | 'pie' | 'area'
   */
  window.erpChart = function(canvasId, type, data, opts) {
    var canvas = document.getElementById(canvasId);
    if (!canvas || typeof Chart === 'undefined') {
      console.warn('Chart.js not loaded or canvas not found:', canvasId);
      return null;
    }

    opts = opts || {};
    var chartType = type === 'area' ? 'line' : type;
    var palette = getPalette();
    var palLight = getPaletteLight();

    // Always assign colors from palette (don't check if already set — Chart.js defaults can interfere)
    if (data.datasets) {
      data.datasets.forEach(function(ds, i) {
        var ci = i % palette.length;
        if (chartType === 'doughnut' || chartType === 'pie') {
          if (!ds.backgroundColor) {
            ds.backgroundColor = palette.slice(0, data.labels ? data.labels.length : palette.length);
          }
          ds.borderColor = ds.borderColor || (readCSSVar('--erp-chart-border') || '#fff');
          ds.borderWidth = ds.borderWidth || 2;
        } else if (type === 'area' || chartType === 'line') {
          ds.borderColor = ds.borderColor || palette[ci];
          if (!ds.backgroundColor) {
            if (type === 'area' && canvas.getContext) {
              ds.backgroundColor = createGradientFill(canvas.getContext('2d'), palette[ci], canvas.height || 300);
            } else {
              ds.backgroundColor = type === 'area' ? hexToRgba(palette[ci], 0.15) : 'transparent';
            }
          }
          ds.pointBackgroundColor = ds.pointBackgroundColor || palette[ci];
          ds.borderWidth = ds.borderWidth || 2;
          if (type === 'area') ds.fill = true;
        } else {
          // Bar charts — ALWAYS force colors + rounded corners
          ds.backgroundColor = ds.backgroundColor || palette[ci];
          ds.hoverBackgroundColor = ds.hoverBackgroundColor || palLight[ci];
          if (ds.borderRadius === undefined) ds.borderRadius = 6;
        }
      });
    }

    var showLegend = (data.datasets && data.datasets.length > 1) || chartType === 'doughnut' || chartType === 'pie';
    var isCircular = chartType === 'doughnut' || chartType === 'pie';

    // Build config — simple object, no deep merge
    var chartOpts = {
      responsive: true,
      maintainAspectRatio: false,
      animation: { duration: 800, easing: 'easeOutQuart' },
      plugins: { legend: { display: showLegend } },
    };

    if (!isCircular) {
      chartOpts.scales = {
        x: { grid: { display: false }, border: { display: false }, ticks: { padding: 8 } },
        y: { beginAtZero: true, border: { display: false }, grid: { color: readCSSVar('--erp-chart-grid') || 'rgba(134,151,196,0.06)' }, ticks: { padding: 8 } },
      };
    }

    // Apply user overrides (shallow — handles cutout, legend position, etc.)
    if (opts) {
      for (var key in opts) {
        if (key === 'plugins' && opts.plugins) {
          chartOpts.plugins = chartOpts.plugins || {};
          for (var pk in opts.plugins) { chartOpts.plugins[pk] = opts.plugins[pk]; }
        } else if (key === 'scales' && opts.scales) {
          chartOpts.scales = chartOpts.scales || {};
          for (var sk in opts.scales) { chartOpts.scales[sk] = opts.scales[sk]; }
        } else {
          chartOpts[key] = opts[key];
        }
      }
    }

    try {
      return new Chart(canvas, { type: chartType, data: data, options: chartOpts });
    } catch (e) {
      console.error('Chart creation failed for', canvasId, e);
      return null;
    }
  };

  /* Hex to rgba */
  function hexToRgba(hex, alpha) {
    if (!hex || hex.charAt(0) !== '#') return 'rgba(0,0,0,' + alpha + ')';
    var r = parseInt(hex.slice(1, 3), 16);
    var g = parseInt(hex.slice(3, 5), 16);
    var b = parseInt(hex.slice(5, 7), 16);
    return 'rgba(' + r + ',' + g + ',' + b + ',' + alpha + ')';
  }

  /* Create vertical gradient fill for area/line charts */
  function createGradientFill(ctx, hex, height) {
    var grad = ctx.createLinearGradient(0, 0, 0, height || 300);
    grad.addColorStop(0, hexToRgba(hex, 0.25));
    grad.addColorStop(1, hexToRgba(hex, 0.02));
    return grad;
  }

  /* Custom HTML tooltip — glassmorphism style */
  function createExternalTooltip(context) {
    var tooltipEl = context.chart.canvas.parentNode.querySelector('.erp-chart-tooltip');
    if (!tooltipEl) {
      tooltipEl = document.createElement('div');
      tooltipEl.className = 'erp-chart-tooltip';
      context.chart.canvas.parentNode.style.position = 'relative';
      context.chart.canvas.parentNode.appendChild(tooltipEl);
    }

    var tooltipModel = context.tooltip;
    if (tooltipModel.opacity === 0) {
      tooltipEl.classList.remove('active');
      return;
    }

    var html = '';
    if (tooltipModel.title && tooltipModel.title.length) {
      html += '<div class="erp-chart-tooltip-title">' + tooltipModel.title[0] + '</div>';
    }
    if (tooltipModel.body) {
      tooltipModel.body.forEach(function(bodyItem, i) {
        var colors = tooltipModel.labelColors[i];
        var swatch = '<span class="erp-chart-tooltip-swatch" style="background:' + colors.backgroundColor + '"></span>';
        var lines = bodyItem.lines;
        lines.forEach(function(line) {
          var parts = line.split(':');
          var label = parts[0] ? parts[0].trim() : '';
          var value = parts[1] ? parts[1].trim() : line;
          html += '<div class="erp-chart-tooltip-row">' + swatch +
            '<span>' + label + '</span>' +
            '<span class="erp-chart-tooltip-value">' + value + '</span></div>';
        });
      });
    }

    tooltipEl.innerHTML = html;
    tooltipEl.classList.add('active');

    var canvasRect = context.chart.canvas.getBoundingClientRect();
    var parentRect = context.chart.canvas.parentNode.getBoundingClientRect();
    tooltipEl.style.left = (tooltipModel.caretX) + 'px';
    tooltipEl.style.top = (tooltipModel.caretY) + 'px';
    tooltipEl.style.transform = 'translate(-50%, -110%)';
  }


  /* Expose global palette for dashboard pages */
  window.ERP_CHART_COLORS = getChartPalette();
  window.ERP_CHART_COLORS_LIGHT = getChartPaletteLight();
  window.ERP_CHART_PALETTE = getChartPalette();

})();
