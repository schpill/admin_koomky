import { startServer } from "./server.js";

startServer().catch((error) => {
  // eslint-disable-next-line no-console
  console.error("failed_to_start_mcp_server", error);
  process.exit(1);
});
