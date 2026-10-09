import api from "./api";
import type { User } from "../types/index";

type LoginResponse = {
success: boolean;
message: string;
user: User;
token: string;
};

type RegisterResponse = {
success: boolean;
message: string;
user: User;
email_verified: boolean;
};

type VerifyEmailResponse = {
success: boolean;
message: string;
user: User;
token: string;
};

type ResendVerificationResponse = {
success: boolean;
message: string;
};

export async function login(
email: string,
password: string
): Promise<LoginResponse> {
const response = await api.post<LoginResponse>("/auth/login", {
email,
password,
});

localStorage.setItem("token", response.data.token);
localStorage.setItem("user", JSON.stringify(response.data.user));

return response.data;

}

export async function register(data: {
first_name: string;
last_name: string;
email: string;
phone?: string;
password: string;
password_confirmation: string;
}): Promise<RegisterResponse> {
const response = await api.post<RegisterResponse>(
"/auth/register",
data
);

return response.data;

}

export async function verifyEmail(
email: string,
code: string
): Promise<VerifyEmailResponse> {
const response = await api.post<VerifyEmailResponse>(
"/auth/verify-email",
{
email,
code,
}
);

localStorage.setItem("token", response.data.token);
localStorage.setItem("user", JSON.stringify(response.data.user));

return response.data;

}

export async function resendVerification(
email: string
): Promise<ResendVerificationResponse> {
const response = await api.post<ResendVerificationResponse>(
"/auth/resend-verification",
{
email,
},
);

return response.data;

}

export async function getCurrentUser(): Promise<User> {
const response = await api.get("/auth/me");

const user = response.data.data ?? response.data.user;

localStorage.setItem("user", JSON.stringify(user));

return user;

}

export async function logout(): Promise<void> {
try {
await api.post("/auth/logout");
} finally {
localStorage.removeItem("token");
localStorage.removeItem("user");
}
}