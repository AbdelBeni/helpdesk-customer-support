export type Role = "Customer" | "Agent" | "Admin";

export interface User {
  id: number;
  first_name: string;
  last_name: string;
  email: string;
  phone?: string | null;
  avatar?: string | null;
  role: {
    id: number;
    name: Role;
  };
  is_active?: boolean;
  last_login_at?: string | null;
}

export interface AuthResponse {
  success: boolean;
  message: string;
  user: User;
  token: string;
}

export interface TicketAssignment {
  id: number;
  agent: User;
  assigned_at: string;
}

export interface Ticket {
  id: number;
  ticket_number: string;
  subject: string;
  description: string;

  category: {
    id: number;
    name: string;
  };

  priority: {
    id: number;
    name: string;
    level: number;
  };

  status: {
    id: number;
    name: string;
    is_closed: boolean;
  };

  customer: User;

  assignment?: TicketAssignment | null;

  created_at: string;
  updated_at: string;
  resolved_at?: string | null;
  closed_at?: string | null;
  first_response_at?: string | null;
}


export interface ApiResponse<T> {
  data: T;
  message?: string;
  success?: boolean;
}

export interface DashboardStats {
  total_tickets: number;
  open_tickets: number;
  in_progress_tickets: number;
  waiting_for_customer_tickets: number;
  resolved_tickets: number;
  closed_tickets: number;
  unassigned_tickets: number;

  tickets_by_priority: {
    id: number;
    name: string;
    level: number;
    count: number;
  }[];

  tickets_by_category: {
    id: number;
    name: string;
    count: number;
  }[];

  average_resolution_time: {
    minutes: number | null;
    hours: number | null;
  };
}

export interface CustomerDashboardStats {
  total_tickets: number;
  open_tickets: number;
  in_progress_tickets: number;
  waiting_for_customer_tickets: number;
  resolved_tickets: number;
  closed_tickets: number;
  average_resolution_time: {
    minutes: number | null;
    hours: number | null;
  };
}