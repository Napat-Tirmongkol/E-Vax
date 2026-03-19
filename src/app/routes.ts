import { createBrowserRouter } from "react-router";
import { Layout } from "./components/Layout";
import { ConsentPage } from "./pages/ConsentPage";
import { ProfilePage } from "./pages/ProfilePage";
import { BookingDatePage } from "./pages/BookingDatePage";
import { BookingTimePage } from "./pages/BookingTimePage";
import { SummaryPage } from "./pages/SummaryPage";

export const router = createBrowserRouter([
  {
    path: "/",
    Component: Layout,
    children: [
      { index: true, Component: ConsentPage },
      { path: "profile", Component: ProfilePage },
      { path: "booking/date", Component: BookingDatePage },
      { path: "booking/time", Component: BookingTimePage },
      { path: "summary", Component: SummaryPage },
    ],
  },
]);
