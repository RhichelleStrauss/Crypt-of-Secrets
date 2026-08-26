import { Renderer, Program, Mesh, Triangle } from 'https://cdn.skypack.dev/ogl';


const config = {
    colors: ["#7A0A0A", "#7A0A0A", "#7A0A0A"],
    speed: 0.1,
    scale: 2.1,
    turbulence: 0.9,
    fluidity: 0.04,
    rimWidth: 0.24,
    sharpness: 2.5,
    shimmer: 1.5,
    glow: 1.4,
    flowDirection: "down",
    opacity: 0.8,
    mouseInteraction: false,
    mouseStrength: 1,
    mouseRadius: 0.35,
    mouseDampening: 0.15
};


const hexToRGB = hex => {
    const c = hex.replace('#', '').padEnd(6, '0');
    return [
        parseInt(c.slice(0, 2), 16) / 255,
        parseInt(c.slice(2, 4), 16) / 255,
        parseInt(c.slice(4, 6), 16) / 255
    ];
};

const prepColors = input => {
    const arr = [];
    for (let i = 0; i < 8; i++) arr.push(hexToRGB(input[Math.min(i, input.length - 1)]));
    const avg = [0, 0, 0];
    for (let i = 0; i < input.length; i++) {
        avg[0] += arr[i][0]; avg[1] += arr[i][1]; avg[2] += arr[i][2];
    }
    avg[0] /= input.length; avg[1] /= input.length; avg[2] /= input.length;
    return { arr, count: input.length, avg };
};

const flowVec = d => d === 'up' ? [0, 1] : d === 'left' ? [-1, 0] : d === 'right' ? [1, 0] : [0, -1];


const vertex = `
attribute vec2 position;
attribute vec2 uv;
varying vec2 vUv;
void main() {
  vUv = uv;
  gl_Position = vec4(position, 0.0, 1.0);
}`;

const fragment = `
precision highp float;
uniform vec3 iResolution; uniform vec2 iMouse; uniform float iTime;
uniform vec3 uColor0; uniform vec3 uColor1; uniform vec3 uColor2; uniform vec3 uColor3;
uniform vec3 uColor4; uniform vec3 uColor5; uniform vec3 uColor6; uniform vec3 uColor7;
uniform int uColorCount; uniform vec3 uMouseColor; uniform vec2 uFlow; uniform float uSpeed;
uniform float uScale; uniform float uTurbulence; uniform float uFluidity; uniform float uRimWidth;
uniform float uSharpness; uniform float uShimmer; uniform float uGlow; uniform float uOpacity;
uniform float uMouseEnabled; uniform float uMouseStrength; uniform float uMouseRadius;
varying vec2 vUv;
#define PI 3.14159265

vec3 palette(float h) {
  int count = uColorCount;
  if (count < 1) count = 1;
  int idx = int(floor(clamp(h, 0.0, 0.999999) * float(count)));
  if (idx <= 0) return uColor0; if (idx == 1) return uColor1; if (idx == 2) return uColor2;
  if (idx == 3) return uColor3; if (idx == 4) return uColor4; if (idx == 5) return uColor5;
  if (idx == 6) return uColor6; return uColor7;
}

float hash(vec3 p3) {
  p3 = fract(p3 * 0.1031);
  p3 += dot(p3, p3.zyx + 33.33);
  return fract((p3.x + p3.y) * p3.z);
}

float smin(float a, float b, float k) {
  float r = exp2(-a / k) + exp2(-b / k);
  return -k * log2(r);
}

float sinlerp(float a, float b, float w) {
  return mix(a, b, (sin(w * PI - PI / 2.0) + 1.0) / 2.0);
}

float vn(vec2 p, float s, float seed) {
  vec2 cellp = floor(p / s); vec2 relp = mod(p, s);
  float g1 = hash(vec3(cellp, seed)); float g2 = hash(vec3(cellp.x + 1.0, cellp.y, seed));
  float g3 = hash(vec3(cellp.x + 1.0, cellp.y + 1.0, seed)); float g4 = hash(vec3(cellp.x, cellp.y + 1.0, seed));
  float bx = sinlerp(g1, g2, relp.x / s); float tx = sinlerp(g4, g3, relp.x / s);
  return sinlerp(bx, tx, relp.y / s);
}

float dbn(vec2 p, float s, float seed) {
  float o = s / 2.0; float n0 = vn(p, s, seed);
  float n1 = vn(p + vec2(o, o), s, seed + 0.1); float n2 = vn(p + vec2(-o, o), s, seed + 0.2);
  float n3 = vn(p + vec2(o, -o), s, seed + 0.3); float n4 = vn(p + vec2(-o, -o), s, seed + 0.4);
  return (2.0 * n0 + 1.5 * n1 + 1.25 * n2 + 1.125 * n3 + n4) / 7.0;
}

void mainImage(out vec4 fragColor, in vec2 fragCoord) {
  float ref = 700.0 / max(uScale, 0.05); vec2 p = fragCoord / iResolution.y * ref;
  float spd = 200.0 * uSpeed; float t = iTime; vec2 dir = uFlow; vec2 perp = vec2(-dir.y, dir.x);
  float distort1 = vn(p + perp * (t * spd), 60.0, 10.0) * 50.0 * uTurbulence;
  float distort2 = vn(p - perp * (t * spd), 120.0, 15.0) * 100.0 * uTurbulence;
  float peaks = dbn(p + distort1 + dir * (t * spd * 0.5), 40.0, 1.0);
  float peaks2 = dbn(p + distort2 - dir * (t * spd * 0.5), 40.0, 0.0);
  float mapeaks = smin(peaks, peaks2, max(uFluidity, 0.001));
  float mGlow = 0.0;
  if (uMouseEnabled > 0.5) {
    vec2 mp = iMouse / iResolution.y * ref; float md = length(p - mp) / ref;
    float rr = max(uMouseRadius, 0.02); mGlow = exp(-md * md / (rr * rr)) * uMouseStrength;
  }
  float band = (uRimWidth - abs((mapeaks - 0.4) * 2.0)) * 5.0;
  float ltn = clamp(band - vn(p + dir * (t * spd * 0.5), 60.0, 12.0) * uShimmer, 0.0, 1.0);
  ltn = pow(ltn, uSharpness) * uGlow; ltn *= clamp(1.0 - mGlow, 0.0, 1.0);
  float h = clamp(0.5 + (peaks - peaks2) * 0.8, 0.0, 1.0); vec3 col = palette(h);
  vec3 outc = col * ltn; float a = clamp(max(outc.r, max(outc.g, outc.b)), 0.0, 1.0);
  fragColor = vec4(outc, a * uOpacity);
}
void main() {
  vec4 color; mainImage(color, vUv * iResolution.xy); gl_FragColor = color;
}
`;


const container = document.getElementById('ferrofluid-container');
const renderer = new Renderer({ dpr: window.devicePixelRatio || 1, alpha: true, antialias: true });
const gl = renderer.gl;
gl.clearColor(0, 0, 0, 0);
gl.canvas.style.width = '100%';
gl.canvas.style.height = '100%';
gl.canvas.style.display = 'block';
container.appendChild(gl.canvas);

const { arr, count, avg } = prepColors(config.colors);
const uniforms = {
    iResolution: { value: [gl.drawingBufferWidth, gl.drawingBufferHeight, 1] },
    iMouse: { value: [0, 0] },
    iTime: { value: 0 },
    uColor0: { value: arr[0] }, uColor1: { value: arr[1] }, uColor2: { value: arr[2] }, uColor3: { value: arr[3] },
    uColor4: { value: arr[4] }, uColor5: { value: arr[5] }, uColor6: { value: arr[6] }, uColor7: { value: arr[7] },
    uColorCount: { value: count }, uMouseColor: { value: avg }, uFlow: { value: flowVec(config.flowDirection) },
    uSpeed: { value: config.speed }, uScale: { value: config.scale }, uTurbulence: { value: config.turbulence },
    uFluidity: { value: config.fluidity }, uRimWidth: { value: config.rimWidth }, uSharpness: { value: config.sharpness },
    uShimmer: { value: config.shimmer }, uGlow: { value: config.glow }, uOpacity: { value: config.opacity },
    uMouseEnabled: { value: config.mouseInteraction ? 1 : 0 }, uMouseStrength: { value: config.mouseStrength },
    uMouseRadius: { value: config.mouseRadius }
};

const program = new Program(gl, { vertex, fragment, uniforms });
const mesh = new Mesh(gl, { geometry: new Triangle(gl), program });


const resize = () => {
    const rect = container.getBoundingClientRect();
    renderer.setSize(rect.width, rect.height);
    uniforms.iResolution.value = [gl.drawingBufferWidth, gl.drawingBufferHeight, 1];
};
window.addEventListener('resize', resize);
resize();


let mouseTarget = [0, 0];
if (config.mouseInteraction) {
    container.addEventListener('pointermove', e => {
        const rect = gl.canvas.getBoundingClientRect();
        const sc = renderer.dpr || 1;
        mouseTarget = [(e.clientX - rect.left) * sc, (rect.height - (e.clientY - rect.top)) * sc];
    });
}


let lastTime = 0;
requestAnimationFrame(function loop(t) {
    requestAnimationFrame(loop);
    uniforms.iTime.value = t * 0.001;
    
    if (!lastTime) lastTime = t;
    const dt = (t - lastTime) / 1000;
    lastTime = t;
    
    if (config.mouseInteraction) {
        const tau = Math.max(1e-4, config.mouseDampening);
        let factor = Math.min(1 - Math.exp(-dt / tau), 1);
        uniforms.iMouse.value[0] += (mouseTarget[0] - uniforms.iMouse.value[0]) * factor;
        uniforms.iMouse.value[1] += (mouseTarget[1] - uniforms.iMouse.value[1]) * factor;
    }

    renderer.render({ scene: mesh });
});