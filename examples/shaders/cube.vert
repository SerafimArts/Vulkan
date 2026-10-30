#version 450

layout(location = 0) in vec3 inPosition;
layout(location = 1) in vec2 inUv;

layout(push_constant) uniform Transform {
    mat4 mvp;
} transform;

layout(location = 0) out vec2 outUv;

void main() {
    gl_Position = transform.mvp * vec4(inPosition, 1.0);
    outUv = inUv;
}
