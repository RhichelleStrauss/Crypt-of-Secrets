<style>
    #mainContent {
        margin-left: 90px;
        transition: margin-left 500ms ease-out;
    }

    aside:hover~#mainContent,
    aside:focus-within~#mainContent {
        margin-left: 400px;
    }

    #goo-container {
        position: absolute;
        top: 0;
        left: 0;
        width: 600px;
        height: 100%;
        pointer-events: none;
        filter: drop-shadow(0 0 22px #7A0A0A);
    }

    #goo-container canvas {
        display: block;
        width: 100%;
        height: 100%;
    }
</style>

<aside class="fixed top-0 left-0 h-screen w-[380px] -translate-x-[300px] hover:translate-x-0 focus-within:translate-x-0 transition-transform duration-500 ease-out z-50">

    <div id="goo-container"></div>

    <div class="relative z-10 w-[280px] h-full flex flex-col pt-12 pl-10">

        <div class="text-[#121110] px-4 py-2 text-center tracking-widest">
            <img src="/crypt-of-secrets/assets/images/icons/CryptLogo.png" alt="Logo" class="w-full h-full object-cover">
        </div>

        <nav class="flex flex-col text-center gap-6 mt-6">
            <a href="home.php" class="text-[#eaddc5] hover:text-[#72685F] text-3xl tracking-widest uppercase transition-colors">
                HOME
            </a>
            <a href="create-post.php" class="text-[#eaddc5] hover:text-[#72685F] text-3xl tracking-widest uppercase transition-colors">
                CREATE POST
            </a>
            <a href="profile.php" class="text-[#eaddc5] hover:text-[#72685F] text-3xl tracking-widest uppercase transition-colors">
                PROFILE
            </a>
            <a href="analytics.php" class="text-[#eaddc5] hover:text-[#72685F] text-3xl tracking-widest uppercase transition-colors">
                ANALYTICS
            </a>
            <a href="awards.php" class="text-[#eaddc5] hover:text-[#72685F] text-3xl tracking-widest uppercase transition-colors">
                AWARDS
            </a>
        </nav>
        <?php if ($user['is_anonymous'] ?? false): ?>
            <a href="claim-profile.php" class="text-[#7A0A0A] hover:text-[#E11C25] text-3xl tracking-widest uppercase transition-colors">
                STEP FORWARD
            </a>
        <?php endif; ?>
    </div>
</aside>

<script type="module">
    import {
        Renderer,
        Program,
        Mesh,
        Triangle
    } from 'https://cdn.skypack.dev/ogl';

    const goo = {
        baseEdge: 440,
        displace: 250,
        feature: 128,
        speed: 0.10,
        grit: 26,
        gritScale: 7,
        dustScale: 3.5,
        dustDepth: 46,
        dustBite: 0.85,
        edgeSoftness: 1.5,
        color: [0.071, 0.067, 0.063]
    };

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
uniform vec2 iResolution;
uniform float iTime;
uniform float uBaseEdge;
uniform float uDisplace;
uniform float uFeature;
uniform float uGrit;
uniform float uGritScale;
uniform float uSoft;
uniform vec3 uColor;
uniform float uDustScale;
uniform float uDustDepth;
uniform float uDustBite;
varying vec2 vUv;
uniform float uHoleScale;
uniform float uHoleReach;
uniform float uHoleBite;

float hash(vec2 p) {
  return fract(sin(dot(p, vec2(127.1, 311.7))) * 43758.5453123);
}

float vnoise(vec2 p) {
  vec2 i = floor(p);
  vec2 f = fract(p);
  f = f * f * (3.0 - 2.0 * f);
  float a = hash(i);
  float b = hash(i + vec2(1.0, 0.0));
  float c = hash(i + vec2(0.0, 1.0));
  float d = hash(i + vec2(1.0, 1.0));
  return mix(mix(a, b, f.x), mix(c, d, f.x), f.y);
}

float fbm(vec2 p) {
  float total = 0.0;
  float amp = 0.5;
  float norm = 0.0;
  for (int i = 0; i < 4; i++) {
    total += vnoise(p) * amp;
    norm += amp;
    p *= 2.0;
    amp *= 0.5;
  }
  return total / norm;
}

void main() {
  vec2 px = vUv * iResolution;
  float t = iTime;

  float r = fbm(px / uFeature + vec2(t * 0.5, t * 0.06));
float g = fbm(px / (uFeature * 1.37) + vec2(-t * 0.31, t * 0.09) + 19.7);
  float fine = fbm(px / uGritScale + vec2(t * 0.9, -t * 0.6));

    float sx = px.x
           + (r - 0.5) * uDisplace
           + (g - 0.5) * uDisplace * 0.22
           + (fine - 0.5) * uGrit;

  float alpha = 1.0 - smoothstep(uBaseEdge - uSoft, uBaseEdge + uSoft, sx);

  float dust = vnoise(px / uDustScale + vec2(t * 1.6, -t * 1.1));
  float band = 1.0 - smoothstep(0.0, uDustDepth, uBaseEdge - sx);
  alpha -= band * (1.0 - smoothstep(0.30, 0.62, dust)) * uDustBite;
  alpha = clamp(alpha, 0.0, 1.0);

    float holes = fbm(px / uHoleScale + vec2(-t * 0.22, t * 0.05) + 51.3);
  float reach = 1.0 - smoothstep(0.0, uHoleReach, uBaseEdge - sx);
  alpha -= reach * (1.0 - smoothstep(0.34, 0.50, holes)) * uHoleBite;
  alpha = clamp(alpha, 0.0, 1.0);

  gl_FragColor = vec4(uColor, alpha);
}
`;

    const container = document.getElementById('goo-container');
    const renderer = new Renderer({
        dpr: window.devicePixelRatio || 1,
        alpha: true,
        antialias: true
    });
    const gl = renderer.gl;
    gl.clearColor(0, 0, 0, 0);
    container.appendChild(gl.canvas);

    const uniforms = {
        iResolution: {
            value: [1, 1]
        },
        iTime: {
            value: 0
        },
        uBaseEdge: {
            value: goo.baseEdge
        },
        uDisplace: {
            value: goo.displace
        },
        uFeature: {
            value: goo.feature
        },
        uGrit: {
            value: goo.grit
        },
        uGritScale: {
            value: goo.gritScale
        },
        uSoft: {
            value: goo.edgeSoftness
        },
        uColor: {
            value: goo.color
        },
        uDustScale: {
            value: goo.dustScale
        },

    };

    const program = new Program(gl, {
        vertex,
        fragment,
        uniforms
    });
    const mesh = new Mesh(gl, {
        geometry: new Triangle(gl),
        program
    });

    function resize() {
        const rect = container.getBoundingClientRect();
        if (rect.width === 0 || rect.height === 0) return;
        renderer.setSize(rect.width, rect.height);
        uniforms.iResolution.value = [rect.width, rect.height];
    }
    window.addEventListener('resize', resize);
    resize();

    requestAnimationFrame(function loop(t) {
        requestAnimationFrame(loop);
        uniforms.iTime.value = t * 0.001 * goo.speed;
        renderer.render({
            scene: mesh
        });
    });
</script>