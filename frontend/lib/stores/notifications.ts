import { create } from "zustand";

export interface AppNotification {
  id: string;
  title: string;
  body: string;
  created_at: string;
  read_at?: string | null;
}

interface NotificationState {
  notifications: AppNotification[];

  setNotifications: (notifications: AppNotification[]) => void;
  addNotification: (notification: AppNotification) => void;
  markAsRead: (id: string) => void;
  markAllAsRead: () => void;
}

export const useNotificationStore = create<NotificationState>((set, get) => ({
  notifications: [],

  setNotifications: (notifications) => set({ notifications }),

  addNotification: (notification) => {
    set({ notifications: [notification, ...get().notifications] });
  },

  markAsRead: (id) => {
    set({
      notifications: get().notifications.map((notification) => {
        if (notification.id !== id || notification.read_at) {
          return notification;
        }

        return {
          ...notification,
          read_at: new Date().toISOString(),
        };
      }),
    });
  },

  markAllAsRead: () => {
    const now = new Date().toISOString();
    set({
      notifications: get().notifications.map((notification) => ({
        ...notification,
        read_at: notification.read_at || now,
      })),
    });
  },
}));
